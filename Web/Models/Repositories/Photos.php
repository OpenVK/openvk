<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\{Photo, User, Club};
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;

class Photos
{
    private $context;
    private $photos;

    private static $cache = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->photos  = $this->context->table("photos");
    }

    private function toPhoto(?ActiveRow $ar): ?Photo
    {
        return is_null($ar) ? null : new Photo($ar);
    }

    public function get(int $id): ?Photo
    {
        return self::$cache[$id] ??= $this->toPhoto($this->photos->get($id));
    }

    public function getByIds(array $ids = []): array
    {
        $photos = $this->photos->select('*')->where('id IN (?)', $ids);
        $payload = [];

        foreach ($photos as $photo) {
            if ($photo->deleted == 1) {
                continue;
            }

            $payload[] = $this->toPhoto($photo);
        }

        return $payload;
    }

    public function getByOwnerAndVIDUnsafe(int $owner, int $vId): ?Photo
    {
        $photo = null;

        if ($owner > 0) {
            $photo = $this->photos->where([
                "owner"      => $owner,
                "virtual_id" => $vId,
            ]);
        } else {
            $photo = $this->photos->where([
                "context_id"  => $owner,
                "context_vid" => $vId,
            ]);
        }

        $photo = $photo->where([
            "system"     => 0,
            "private"    => 0,
            "deleted"    => 0,
        ])->fetch();

        return $this->toPhoto($photo);
    }

    public function getByOwnerAndVID(int $owner, int $vId, ?string $access_key = null): ?Photo
    {
        $photo = $this->getByOwnerAndVIDUnsafe($owner, $vId);

        if (is_null($photo)) {
            return null;
        }

        if (!$photo->checkAccessKey($access_key)) {
            return null;
        }

        return $photo;
    }

    public function getEveryUserPhoto(User $user, int $offset = 0, int $limit = 10): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $photos = $this->photos->where([
            "owner"    => $user->getId(),
            "deleted"  => 0,
            "system"   => 0,
            "private"  => 0,
            "anonymous" => 0,
        ])->order("id DESC");

        foreach ($photos->limit($limit, $offset) as $photo) {
            yield $this->toPhoto($photo);
        }
    }

    public function getUserPhotosCount(User $user)
    {
        return $this->photos->where([
            "owner"    => $user->getId(),
            "deleted"  => 0,
            "system"   => 0,
            "private"  => 0,
            "anonymous" => 0,
        ])->count("*");
    }

    public function getClubPhotosCount(Club $club)
    {
        return $this->photos->where([
            "owner"    => $club->getId() * -1,
            "deleted"  => 0,
            "system"   => 0,
            "private"  => 0,
            "anonymous" => 0,
        ])->count("*");
    }
}
