<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Video;
use Chandler\Database\DatabaseConnection;

class Videos
{
    private $context;
    private $videos;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->videos  = $this->context->table("videos");
    }

    public function get(int $id): ?Video
    {
        $videos = $this->videos->get($id);
        if (!$videos) {
            return null;
        }

        return new Video($videos);
    }

    public function getByOwnerAndVID(int $owner, int $vId): ?Video
    {
        $videos = $this->videos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();
        if (!$videos) {
            return null;
        }

        return new Video($videos);
    }

    public function getByUser(User $user, int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        foreach ($this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0])->page($page, $perPage)->order("created DESC") as $video) {
            yield new Video($video);
        }
    }

    public function getByUserLimit(User $user, int $offset = 0, int $limit = 10): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        foreach ($this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0])->limit($limit, $offset)->order("created DESC") as $video) {
            yield new Video($video);
        }
    }

    public function getUserVideosCount(User $user): int
    {
        return sizeof($this->videos->where("owner", $user->getId())->where(["deleted" => 0, "unlisted" => 0]));
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
