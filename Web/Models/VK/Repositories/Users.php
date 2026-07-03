<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\User as VKUser;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * VK-репозиторий пользователей — имитация openvk\Web\Models\Repositories\Users.
 */
class Users
{
    /** @var VKUser[] */
    private static array $cache = [];

    /** @var VKUser|null Кешированный результат для getByChandlerUser/getByChandlerUserId. */
    private static ?VKUser $currentUser = null;

    public function get(int $id): ?VKUser
    {
        return self::$cache[$id] ??= VKUser::load($id);
    }

    /**
     * Поиск пользователя по screen_name.
     */
    public function getByShortURL(string $url): ?VKUser
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

        if (empty($response) || ($response["type"] ?? "") !== "user") {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = $this->get(
            (int) $response["object_id"],
        );
    }

    /**
     * Поиск по полному адресу (с id или domain).
     */
    public function getByAddress(string $address): ?VKUser
    {
        if (strpos($address, "id") === 0) {
            $id = (int) substr($address, 2);
            if ($id > 0) {
                return $this->get($id);
            }
        }

        return $this->getByShortURL($address);
    }

    /**
     * Возвращает текущего пользователя VK через API-токен.
     * Результат кешируется статически на время запроса.
     */
    private function getCurrentUser(): ?VKUser
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        try {
            $response = VKAPIClient::i()->call("account.getProfileInfo", []);
        } catch (\Throwable) {
            return self::$currentUser = null;
        }

        if (empty($response) || !isset($response["id"])) {
            return self::$currentUser = null;
        }

        return self::$currentUser = $this->get((int) $response["id"]);
    }

    /**
     * Поиск VK-пользователя по объекту ChandlerUser.
     * В VK-режиме определяется через токен доступа.
     */
    public function getByChandlerUser(?\Chandler\Security\User $user): ?VKUser
    {
        return $this->getCurrentUser();
    }

    /**
     * Поиск VK-пользователя по GUID Chandler.
     * В VK-режиме определяется через токен доступа.
     */
    public function getByChandlerUserId(string $cid): ?VKUser
    {
        return $this->getCurrentUser();
    }

    /**
     * Загружает несколько пользователей по массиву ID.
     *
     * @param int[] $ids
     * @return VKUser[]
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
            $response = VKAPIClient::i()->usersGet($uncached, [
                "photo_50",
                "photo_100",
                "photo_200",
                "photo_max_orig",
                "sex",
                "status",
                "screen_name",
                "online",
                "verified",
                "followers_count",
                "about",
                "counters",
                "last_seen",
            ]);

            foreach ($response as $item) {
                $vkUser = new VKUser($item);
                self::$cache[$vkUser->getId()] = $vkUser;
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
     * Поиск пользователей.
     * VK: users.search
     */
    public function find(
        string $query = "",
        array $params = [],
        array $order = ["type" => "id", "invert" => false],
        int $page = 1,
        ?int $perPage = null,
    ): VKEntityStream {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query, $params, $order) {
                $vkParams = [
                    "q" => $query,
                    "count" => min($count, 1000),
                    "offset" => $offset,
                    "fields" =>
                        "photo_50,photo_100,photo_200,status,screen_name,online,verified,last_seen",
                    "sort" => ($order["invert"] ?? false) ? 0 : 1,
                ];

                // Маппинг параметров OpenVK → VK API (только значащие значения)
                foreach ($params as $k => $v) {
                    if (is_null($v) || $v === "" || $v === false) {
                        continue;
                    }

                    switch ($k) {
                        case "gender":
                            $val = (int) $v;
                            if ($val > 0 && $val < 3) {
                                $vkParams["sex"] = $val;
                            }
                            break;
                        case "hometown":
                            if (!empty($v)) {
                                $vkParams["hometown"] = $v;
                            }
                            break;
                        case "city":
                            if (!empty($v)) {
                                $vkParams["city"] = $v;
                            }
                            break;
                        case "marital_status":
                            $val = (int) $v;
                            if ($val > 0) {
                                $vkParams["status"] = $val;
                            }
                            break;
                        case "is_online":
                            $vkParams["online"] = 1;
                            break;
                        case "fav_mus":
                            if (!empty($v)) {
                                $vkParams["music"] = 1;
                            }
                            break;
                    }
                }

                try {
                    $response = VKAPIClient::i()->call("users.search", $vkParams);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKUser($data),
        );
    }

    /**
     * Статистика — заглушка.
     */
    public function getStatistics(): object
    {
        return (object) [
            "users" => "N/A (VK mode)",
            "today" => "N/A",
            "online" => "N/A",
            "verified" => 0,
        ];
    }

    public function getCount(): int
    {
        return 0;
    }

    /**
     * Заглушка.
     */
    public function getInstanceAdmins(bool $excludeHidden = true): \Traversable
    {
        return new \ArrayIterator([]);
    }
}
