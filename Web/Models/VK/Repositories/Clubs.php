<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Club as VKClub;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * VK-репозиторий групп — имитация openvk\Web\Models\Repositories\Clubs.
 */
class Clubs
{
    /** @var VKClub[] */
    private static array $cache = [];

    public function get(int $id): ?VKClub
    {
        return self::$cache[$id] ??= VKClub::load($id);
    }

    public function getByShortURL(string $url): ?VKClub
    {
        if (is_numeric($url)) {
            return $this->get((int) $url);
        }

        $cacheKey = "short_$url";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("utils.resolveScreenName", [
                "screen_name" => $url,
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        if (
            empty($response) ||
            !in_array($response["type"] ?? "", ["group", "page", "event"])
        ) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = $this->get(
            (int) $response["object_id"],
        );
    }

    /**
     * @param int[] $ids
     * @return VKClub[]
     */
    public function getByIds(array $ids = []): array
    {
        $uncached = [];
        foreach ($ids as $id) {
            if (!isset(self::$cache[$id])) {
                $uncached[] = $id;
            }
        }

        if (!empty($uncached)) {
            try {
                $response = VKAPIClient::i()->groupsGetById($uncached, [
                    "photo_50",
                    "photo_100",
                    "photo_200",
                    "photo_max_orig",
                    "members_count",
                    "description",
                    "status",
                    "screen_name",
                    "type",
                    "is_closed",
                    "verified",
                    "cover",
                ]);
            } catch (\Throwable) {
                $response = [];
            }

            foreach ($response as $item) {
                $vkClub = new VKClub($item);
                self::$cache[$vkClub->getId()] = $vkClub;
            }
        }

        $result = [];
        foreach ($ids as $id) {
            if (isset(self::$cache[$id])) {
                $result[] = self::$cache[$id];
            }
        }

        return $result;
    }

    /**
     * Поиск групп.
     * VK: groups.search
     */
    public function find(
        string $query = "",
        array $params = [],
        array $order = ["type" => "id", "invert" => false],
        int $page = 1,
        ?int $perPage = null,
    ): VKEntityStream {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query, $order) {
                try {
                    $response = VKAPIClient::i()->call("groups.search", [
                        "q" => $query,
                        "count" => min($count, 1000),
                        "offset" => $offset,
                        "fields" =>
                            "photo_50,photo_100,photo_200,members_count,description,status,screen_name,type,is_closed",
                        "sort" => 0,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKClub($data),
        );
    }

    /**
     * @return VKClub[]
     */
    public function getByUser(int $userId): array
    {
        return VKClub::getByUser($userId);
    }

    public function getCount(): int
    {
        return 0;
    }

    /**
     * Загружает группу по полному адресу.
     */
    public function getByAddress(string $address): ?VKClub
    {
        if (strpos($address, "club") === 0) {
            $id = (int) substr($address, 4);
            if ($id > 0) {
                return $this->get($id);
            }
        } elseif (strpos($address, "public") === 0) {
            $id = (int) substr($address, 6);
            if ($id > 0) {
                return $this->get($id);
            }
        }

        return $this->getByShortURL($address);
    }
}
