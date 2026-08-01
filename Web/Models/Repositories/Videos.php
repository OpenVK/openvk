<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Video;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;

class Videos
{
    private static $cache = [];

    private $context;
    private $videos;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->videos  = $this->context->table("videos");
    }

    private function toVideo(?ActiveRow $ar): ?Video
    {
        return is_null($ar) ? null : new Video($ar);
    }

    public function get(int $id): ?Video
    {
        return self::$cache[$id] ??= $this->toVideo($this->videos->get($id));
    }

    public function getByOwnerAndVIDUnsafe(int $owner, int $vId): ?Video
    {
        $video = $this->videos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();

        return $this->toVideo($video);
    }

    public function getByOwnerAndVID(int $owner, int $vId, ?string $access_key = null): ?Video
    {
        $video = null;

        if ($owner > 0) {
            $video = $this->videos->where([
                "owner"      => $owner,
                "virtual_id" => $vId,
            ])->fetch();
        } else {
            $video = $this->videos->where([
                "context_id"  => $owner,
                "context_vid" => $vId,
            ])->fetch();
        }

        if (is_null($video)) {
            return null;
        }

        $n_video = new Video($video);

        # If video is from group, do not allow to view it like it a user's video
        if ($owner > 0 && $n_video->hasContext()) {
            return null;
        }

        if (!$n_video->checkAccessKey($access_key)) {
            return null;
        }

        return $n_video;
    }

    public function getByUser($user, int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $request = $this->videos;

        if ($user->getRealId() > 0) {
            $request = $request->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0, "context_id" => null]);
        } else {
            $request = $request->where("context_id", $user->getRealId())->where(["deleted" => 0, "unlisted" => 0, "context_unlisted" => 0]);
        }

        foreach ($request->page($page, $perPage)->order("created DESC") as $video) {
            yield new Video($video);
        }
    }

    public function getByUserLimit($user, int $offset = 0, int $limit = 10): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        foreach ($this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0])->limit($limit, $offset)->order("created DESC") as $video) {
            yield new Video($video);
        }
    }

    public function getUserVideosCount($user): int
    {
        if ($user->getRealId() > 0) {
            return $this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0, "context_id" => null])->count();
        } else {
            return $this->videos->where("context_id", $user->getRealId())->where(["deleted" => 0, "unlisted" => 0, "context_unlisted" => 0])->count();
        }
    }

    public function find(string $query = "", array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $query = "%$query%";
        $result = $this->videos->where("CONCAT_WS(' ', name, description) LIKE ?", $query)->where("deleted", 0)->where("unlisted", 0);
        $order_str = 'id';

        switch ($order['type']) {
            case 'id':
                $order_str = 'id ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        foreach ($params as $paramName => $paramValue) {
            switch ($paramName) {
                case "before":
                    $result->where("created < ?", $paramValue);
                    break;
                case "after":
                    $result->where("created > ?", $paramValue);
                    break;
                case 'only_youtube':
                    if ((int) $paramValue != 1) {
                        break;
                    }
                    $result->where("link != ?", 'NULL');
                    break;
            }
        }

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Video", $result);
    }

    public function getLastVideo(User $user)
    {
        $video = $this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0])->order("id DESC")->fetch();

        return new Video($video);
    }
}
