<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Album as VKAlbum;
use openvk\Web\Models\VK\Entities\User as VKUser;
use openvk\Web\Models\VK\Entities\Club as VKClub;

/**
 * VK-репозиторий альбомов — имитация openvk\Web\Models\Repositories\Albums.
 */
class Albums
{
    /** @var VKAlbum[] */
    private static array $cache = [];

    public function get(int $id): ?VKAlbum
    {
        return self::$cache[$id] ??= $this->loadByOwnerAndId(0, $id);
    }

    /**
     * Загружает альбом по owner_id и album_id.
     */
    public function loadByOwnerAndId(int $ownerId, int $albumId): ?VKAlbum
    {
        $cacheKey = "{$ownerId}_{$albumId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("photos.getAlbums", [
                "owner_id"  => $ownerId,
                "album_ids" => $albumId,
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        $items = $response["items"] ?? [];
        if (empty($items)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKAlbum($items[0]);
    }

    /**
     * @return VKAlbum[]
     */
    public function getUserAlbums(VKUser $user, int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        try {
            $response = VKAPIClient::i()->call("photos.getAlbums", [
                "owner_id" => $user->getId(),
                "offset"   => $offset,
                "count"    => min($perPage, 100),
                "need_covers" => 1,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $albums = [];
        foreach ($response["items"] ?? [] as $item) {
            $albums[] = new VKAlbum($item);
        }

        return $albums;
    }

    public function getUserAlbumsCount(VKUser $user): int
    {
        try {
            $response = VKAPIClient::i()->call("photos.getAlbums", [
                "owner_id" => $user->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($response["count"] ?? 0);
    }

    /**
     * Заглушка: VK API не имеет понятия "альбом аватара".
     */
    public function getUserAvatarAlbum(VKUser $user): ?VKAlbum
    {
        return null;
    }

    /**
     * @return VKAlbum[]
     */
    public function getClubAlbums(VKClub $club, int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        try {
            $response = VKAPIClient::i()->call("photos.getAlbums", [
                "owner_id" => $club->getOwnerId(),
                "offset"   => $offset,
                "count"    => min($perPage, 100),
            ]);
        } catch (\Throwable) {
            return [];
        }

        $albums = [];
        foreach ($response["items"] ?? [] as $item) {
            $albums[] = new VKAlbum($item);
        }

        return $albums;
    }

    public function getClubAlbumsCount(VKClub $club): int
    {
        try {
            $response = VKAPIClient::i()->call("photos.getAlbums", [
                "owner_id" => $club->getOwnerId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($response["count"] ?? 0);
    }
}
