<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Photo as VKPhoto;
use openvk\Web\Models\VK\Entities\User as VKUser;

/**
 * VK-репозиторий фото — имитация openvk\Web\Models\Repositories\Photos.
 */
class Photos
{
    /** @var VKPhoto[] */
    private static array $cache = [];

    /**
     * VK API не имеет прямого get(id).
     * Используйте getByOwnerAndVID().
     */
    public function get(int $id): ?VKPhoto
    {
        throw new \RuntimeException(
            "VK API: photos.get requires owner_id. Use getByOwnerAndVID(owner, vid)."
        );
    }

    /**
     * Получает фото по owner и virtual_id.
     * VK: photos.getById
     */
    public function getByOwnerAndVID(int $owner, int $vId): ?VKPhoto
    {
        $cacheKey = "{$owner}_{$vId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $response = VKAPIClient::i()->call("photos.getById", [
            "photos" => "{$owner}_{$vId}",
        ]);

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKPhoto($response[0]);
    }

    /**
     * Получает все фото пользователя.
     * VK: photos.getAll
     *
     * @return VKPhoto[]
     */
    public function getEveryUserPhoto(VKUser $user, int $offset = 0, int $limit = 10): array
    {
        $response = VKAPIClient::i()->call("photos.getAll", [
            "owner_id" => $user->getId(),
            "offset"   => $offset,
            "count"    => min($limit, 200),
            "extended" => 0,
            "need_likes" => 1
        ]);

        $photos = [];
        foreach ($response["items"] ?? [] as $item) {
            $photos[] = new VKPhoto($item);
        }

        return $photos;
    }

    /**
     * Количество фото пользователя.
     */
    public function getUserPhotosCount(VKUser $user): int
    {
        return (int) VKAPIClient::i()->call("photos.getAll", [
            "owner_id" => $user->getId(),
            "count"    => 0,
        ])["count"] ?? 0;
    }

    /**
     * Загружает фото из альбома.
     * VK: photos.get
     */
    public function getByAlbum(int $ownerId, int $albumId, int $offset = 0, int $limit = 50): array
    {
        $response = VKAPIClient::i()->call("photos.get", [
            "owner_id" => $ownerId,
            "album_id" => $albumId,
            "offset"   => $offset,
            "count"    => min($limit, 200),
            "need_likes" => 1,
        ]);

        $photos = [];
        foreach ($response["items"] ?? [] as $item) {
            $photos[] = new VKPhoto($item);
        }

        return $photos;
    }
}
