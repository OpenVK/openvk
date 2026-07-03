<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Video as VKVideo;
use openvk\Web\Models\VK\Entities\User as VKUser;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * VK-репозиторий видео — имитация openvk\Web\Models\Repositories\Videos.
 */
class Videos
{
    /** @var VKVideo[] */
    private static array $cache = [];

    /**
     * VK API не имеет прямого get(id). Используйте getByOwnerAndVID().
     */
    public function get(int $id): ?VKVideo
    {
        throw new \RuntimeException(
            "VK API: videos.get requires owner_id. Use getByOwnerAndVID(owner, vid)."
        );
    }

    /**
     * Получает видео по owner и virtual_id.
     * VK: video.get
     */
    public function getByOwnerAndVID(int $owner, int $vId): ?VKVideo
    {
        $cacheKey = "{$owner}_{$vId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $response = VKAPIClient::i()->call("video.get", [
            "videos" => "{$owner}_{$vId}",
        ]);

        $items = $response["items"] ?? [];
        if (empty($items)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKVideo($items[0]);
    }

    /**
     * Возвращает видео пользователя.
     * VK: video.get с параметром owner_id.
     *
     * @return VKVideo[]
     */
    public function getByUser(VKUser $user, int $page = 1, ?int $perPage = null): array
    {
        try {
            $perPage ??= OPENVK_DEFAULT_PER_PAGE;
            $offset = $perPage * ($page - 1);
            $response = VKAPIClient::i()->call("video.get", [
                "owner_id" => $user->getId(),
                "offset"   => $offset,
                "count"    => min($perPage, 200),
            ]);

            $videos = [];
            foreach ($response["items"] ?? [] as $item) {
                $videos[] = new VKVideo($item);
            }

            return $videos;
        } catch(\Exception $e) {
            return [];
        }
    }

    /**
     * Возвращает видео пользователя с лимитом.
     *
     * @return VKVideo[]
     */
    public function getByUserLimit(VKUser $user, int $offset = 0, int $limit = 10): array
    {
        $response = VKAPIClient::i()->call("video.get", [
            "owner_id" => $user->getId(),
            "offset"   => $offset,
            "count"    => min($limit, 200),
        ]);

        $videos = [];
        foreach ($response["items"] ?? [] as $item) {
            $videos[] = new VKVideo($item);
        }

        return $videos;
    }

    /**
     * Количество видео пользователя.
     */
    public function getUserVideosCount(VKUser $user): int
    {
        $response = VKAPIClient::i()->call("video.get", [
            "owner_id" => $user->getId(),
            "count"    => 0,
        ]);

        return (int) ($response["count"] ?? 0);
    }

    /**
     * Поиск видео.
     * VK: video.search
     */
    public function find(string $query = "", array $params = [], array $order = ['type' => 'id', 'invert' => false]): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query, $order): array {
                return VKAPIClient::i()->call("video.search", [
                    "q"      => $query,
                    "offset" => $offset,
                    "count"  => $count,
                    "sort"   => ($order["type"] ?? "id") === "duration" ? 2 : 0,
                ]);
            },
            fn(array $data): VKVideo => new VKVideo($data)
        );
    }
}
