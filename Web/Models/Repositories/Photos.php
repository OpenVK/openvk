<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\{Photo, User};
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

    public function getByOwnerAndVIDUnsafe(int $owner, int $vId): ?Photo
    {
        $photo = $this->photos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
            "system"     => 0,
            "private"    => 0,
        ])->fetch();

        return $this->toPhoto($photo);
    }

    public function getByOwnerAndVID(int $owner, int $vId, ?string $access_key = null): ?Photo
    {
        $photo = $this->photos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
            "system"     => 0,
        ])->fetch();

        if (is_null($photo)) {
            return null;
        }

        $n_photo = new Photo($photo);
        if (!$n_photo->checkAccessKey($access_key)) {
            return null;
        }

        return $n_photo;
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
}
