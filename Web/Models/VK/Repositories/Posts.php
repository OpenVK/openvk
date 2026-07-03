<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Post as VKPost;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * Репозиторий постов, работающий через VK API.
 *
 * Заменяет openvk\Web\Models\Repositories\Posts,
 * но не наследует его — имеет те же публичные методы.
 */
class Posts
{
    /** @var VKPost[] */
    private static array $cache = [];

    /**
     * VK API не имеет прямого аналога get(int $id),
     * так как post_id уникален только в пределах владельца (owner_id).
     * Этот метод выбрасывает исключение — используйте getByOwnerAndVID().
     */
    public function get(int $id): ?VKPost
    {
        throw new \RuntimeException(
            "VK API: posts.get requires owner_id. Use getByOwnerAndVID(owner_id, post_id) instead.",
        );
    }

    /**
     * Получает пост по owner_id и virtual_id (номер поста на стене).
     * VK: wall.getById
     */
    public function getByOwnerAndVID(int $ownerId, int $postId): ?VKPost
    {
        $cacheKey = "{$ownerId}_{$postId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $response = VKAPIClient::i()->call("wall.getById", [
            "posts" => "{$ownerId}_{$postId}",
        ]);

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKPost($response[0]);
    }

    /**
     * Посты со стены пользователя/группы.
     * VK: wall.get
     *
     * @return VKPost[]
     */
    public function getPostsFromUsersWall(
        int $user,
        int $page = 1,
        ?int $perPage = null,
        ?int $offset = null,
    ): array {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        return VKPost::getWall($user, $perPage, $offset);
    }

    /**
     * Лента новостей текущего пользователя.
     * VK: newsfeed.get
     *
     * @return array{items: VKPost[], profiles: \openvk\Web\Models\VK\Entities\User[], groups: \openvk\Web\Models\VK\Entities\Club[]}
     */
    public function getGlobalFeed(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;

        return VKPost::getNewsfeed(["post", "photo"], $perPage);
    }

    /**
     * Поиск постов.
     * VK: newsfeed.search
     */
    public function find(
        string $query = "",
        array $params = [],
        array $order = ["type" => "id", "invert" => false],
        int $page = 1,
        ?int $perPage = null,
    ): VKEntityStream {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query, $params) {
                $vkParams = [
                    "q" => $query,
                    "count" => min($count, 200),
                    "offset" => $offset,
                ];

                if (!empty($params["owner_id"])) {
                    $vkParams["owner_id"] = $params["owner_id"];
                }

                try {
                    $response = VKAPIClient::i()->call("newsfeed.search", $vkParams);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKPost($data),
        );
    }

    /**
     * Количество постов на стене пользователя (все записи).
     * VK: wall.get
     */
    public function getPostCountOnUserWall(int $user): int
    {
        $response = VKAPIClient::i()->wallGet($user, 0, 0);

        return $response["count"] ?? 0;
    }

    /**
     * Количество постов на стене пользователя, написанных владельцем.
     * VK: wall.get с filter=owner
     */
    public function getOwnersCountOnUserWall(int $user): int
    {
        $response = VKAPIClient::i()->call("wall.get", [
            "owner_id" => $user,
            "filter" => "owner",
            "count" => 0,
            "offset" => 0,
        ]);

        return $response["count"] ?? 0;
    }

    /**
     * Количество постов на стене пользователя, написанных другими.
     * VK: wall.get с filter=others
     */
    public function getOthersCountOnUserWall(int $user): int
    {
        $response = VKAPIClient::i()->call("wall.get", [
            "owner_id" => $user,
            "filter" => "others",
            "count" => 0,
            "offset" => 0,
        ]);

        return $response["count"] ?? 0;
    }

    /**
     * Посты со стены пользователя, написанные владельцем.
     * VK: wall.get с filter=owner
     *
     * @return VKPost[]
     */
    public function getOwnersPostsFromWall(
        int $user,
        int $page = 1,
        ?int $perPage = null,
    ): array {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        $response = VKAPIClient::i()->call("wall.get", [
            "owner_id" => $user,
            "filter" => "owner",
            "count" => $perPage,
            "offset" => $offset,
        ]);

        $result = [];
        foreach ($response["items"] ?? [] as $item) {
            $result[] = new VKPost($item);
        }

        return $result;
    }

    /**
     * Посты со стены пользователя, написанные другими.
     * VK: wall.get с filter=others
     *
     * @return VKPost[]
     */
    public function getOthersPostsFromWall(
        int $user,
        int $page = 1,
        ?int $perPage = null,
    ): array {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        $response = VKAPIClient::i()->call("wall.get", [
            "owner_id" => $user,
            "filter" => "others",
            "count" => $perPage,
            "offset" => $offset,
        ]);

        $result = [];
        foreach ($response["items"] ?? [] as $item) {
            $result[] = new VKPost($item);
        }

        return $result;
    }

    /**
     * Получает один пост по wall_id и post_id.
     * VK: wall.getById
     */
    public function getPostById(int $wall, int $post): ?VKPost
    {
        $response = VKAPIClient::i()->call("wall.getById", [
            "posts" => "{$wall}_{$post}",
        ]);

        if (empty($response)) {
            return null;
        }

        return new VKPost($response[0]);
    }

    /**
     * Количество постов — заглушка.
     */
    public function getCount(): int
    {
        return 0;
    }

    /**
     * Получает несколько постов по массиву идентификаторов.
     * VK: wall.getById
     *
     * @param string[] $posts Массив строк "owner_id_post_id"
     * @return VKPost[]
     */
    public function getByIds(array $posts = []): array
    {
        if (empty($posts)) {
            return [];
        }

        $response = VKAPIClient::i()->call("wall.getById", [
            "posts" => implode(",", $posts),
        ]);

        $result = [];
        foreach ($response as $item) {
            $post = new VKPost($item);
            $result[] = $post;
        }

        return $result;
    }
}
