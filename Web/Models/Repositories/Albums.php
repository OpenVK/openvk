<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Album;
use openvk\Web\Models\Entities\Photo;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Albums
{
    private $context;
    private $albums;

    /* aggressive sql caching */
    private static $cache = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->albums  = $this->context->table("albums");
    }

    private function toAlbum(?ActiveRow $ar): ?Album
    {
        return is_null($ar) ? null : new Album($ar);
    }

    private function getSpecialConditions(int $id, int $type): array
    {
        return [
            "name"  => "[/!\\ DO NOT EDIT: INTERNAL NAME ASSIGNMENT IS ACTIVE]",
            "owner" => $id,
            "special_type" => $type,
        ];
    }

    public function get(int $id): ?Album
    {
        return self::$cache[$id] ??= $this->toAlbum($this->albums->get($id));
    }

    private function getUserQuery(User $user, ?User $for): \Nette\Database\Table\Selection
    {
        $albums  = $this->albums->where("owner", $user->getId())->where("deleted", false);

        if (!$user->getPrivacyPermission('photos.read_saved', $for)) {
            $albums->where("special_type NOT", [Album::SPECIAL_SAVED]);
        }
        return $albums;
    }

    public function getUserAlbums(User $user, int $page = 1, ?int $perPage = null, ?User $for): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $albums  = $this->getUserQuery($user, $for);

        foreach ($albums->page($page, $perPage) as $album) {
            yield new Album($album);
        }
    }

    public function getUserAlbumsCount(User $user, ?User $for): int
    {
        $albums  = $this->getUserQuery($user, $for);
        return sizeof($albums);
    }

    public function getClubAlbums(Club $club, int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $albums  = $this->albums->where("owner", $club->getId() * -1)->where("special_type", 0)->where("deleted", false);
        foreach ($albums->page($page, $perPage) as $album) {
            yield new Album($album);
        }
    }

    public function getClubAlbumsCount(Club $club): int
    {
        $albums = $this->albums->where("owner", $club->getId() * -1)->where("special_type", 0)->where("deleted", false);
        return sizeof($albums);
    }

    public function getAvatarAlbumById(int $id, int $regTime): Album
    {
        $data  = $this->getSpecialConditions($id, Album::SPECIAL_AVATARS);
        return $this->getOrCreateSpecialAlbum($id, Album::SPECIAL_AVATARS, $regTime);
    }

    public function getUserAvatarAlbum(User $user): Album
    {
        return $this->getAvatarAlbumById($user->getId(), $user->getRegistrationTime()->timestamp());
    }

    public function getClubAvatarAlbum(Club $club): Album
    {
        return $this->getAvatarAlbumById($club->getId() * -1, time());
    }

    public function getUserWallAlbum(User $user): Album
    {
        $data  = $this->getSpecialConditions($user->getId(), Album::SPECIAL_WALL);

        return $this->getOrCreateSpecialAlbum($user->getId(), Album::SPECIAL_WALL, $user->getRegistrationTime()->timestamp());
    }

    public function getAlbumByPhotoId(Photo $photo): ?Album
    {
        $dbalbum = $this->context->table("album_relations")->where(["media" => $photo->getId()])->fetch();

        return $dbalbum->collection ? $this->get($dbalbum->collection) : null;
    }

    public function getAlbumByOwnerAndId(int $owner, int $id)
    {
        $album = $this->albums->where([
            "owner" => $owner,
            "id"    => $id,
        ])->fetch();

        return $album ? new Album($album) : null;
    }

    public function getUserSavedAlbum(User $user): Album
    {
        return $this->getOrCreateSpecialAlbum($user->getId(), Album::SPECIAL_SAVED, $user->getRegistrationTime()->timestamp());
    }

    private function getOrCreateSpecialAlbum(int $ownerId, int $specialType, int $regTime): Album
    {
        $album = $this->albums->where([
            "owner" => $ownerId,
            "special_type" => $specialType,
        ])->fetch();

        if (!$album) {
            $album = new Album();
            $album->setName("[!!! internal album]");
            $album->setOwner($ownerId);
            $album->setSpecial_Type($specialType);
            $album->setCreated($regTime);
            $album->save();

            return $album;
        }

        return new Album($album);
    }
}
