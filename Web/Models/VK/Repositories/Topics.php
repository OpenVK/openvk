<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Topic as VKTopic;
use openvk\Web\Models\VK\Entities\Club as VKClub;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * VK-репозиторий тем обсуждений — имитация через VK API board.getTopics.
 */
class Topics
{
    /** @var VKTopic[] */
    private static array $cache = [];

    /**
     * VK API не имеет прямого аналога get(int $id),
     * так как topic_id уникален только в пределах группы.
     * Используйте getTopicById(int $club, int $topic).
     */
    public function get(int $id): ?VKTopic
    {
        throw new \RuntimeException(
            "VK API: board.getTopics requires group_id. Use getTopicById(club, topic).",
        );
    }

    /**
     * Получает тему по ID группы и ID темы.
     * VK: board.getTopics с параметром topic_ids.
     */
    public function getTopicById(int $club, int $topic): ?VKTopic
    {
        $cacheKey = "topic_{$club}_{$topic}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("board.getTopics", [
                "group_id"  => $club,
                "topic_ids" => $topic,
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        $items = $response["items"] ?? [];
        if (empty($items)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKTopic($items[0]);
    }

    /**
     * Возвращает темы обсуждений группы.
     * VK: board.getTopics
     */
    public function getClubTopics(VKClub $club, int $page = 1, ?int $perPage = null): VKEntityStream
    {
        $clubId = $club->getId();

        return new VKEntityStream(
            function(int $offset, int $count) use ($clubId) {
                try {
                    $response = VKAPIClient::i()->call("board.getTopics", [
                        "group_id" => $clubId,
                        "count"    => min($count, 100),
                        "offset"   => $offset,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKTopic($data),
        );
    }

    /**
     * Количество тем в группе.
     * VK: board.getTopics
     */
    public function getClubTopicsCount(VKClub $club): int
    {
        try {
            $response = VKAPIClient::i()->call("board.getTopics", [
                "group_id" => $club->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($response["count"] ?? 0);
    }

    /**
     * Возвращает последние темы группы (сортировка по дате обновления).
     * VK: board.getTopics с order=1
     */
    public function getLastTopics(VKClub $club, ?int $count = null): VKEntityStream
    {
        $clubId = $club->getId();

        return new VKEntityStream(
            function(int $offset, int $count) use ($clubId) {
                try {
                    $response = VKAPIClient::i()->call("board.getTopics", [
                        "group_id" => $clubId,
                        "count"    => min($count, 100),
                        "offset"   => $offset,
                        "order"    => 1,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKTopic($data),
        );
    }

    /**
     * Поиск тем по тексту.
     * VK: board.getTopics с параметром query.
     */
    public function find(VKClub $club, string $query): VKEntityStream
    {
        $clubId = $club->getId();

        return new VKEntityStream(
            function(int $offset, int $count) use ($clubId, $query) {
                try {
                    $response = VKAPIClient::i()->call("board.getTopics", [
                        "group_id" => $clubId,
                        "query"    => $query,
                        "count"    => min($count, 100),
                        "offset"   => $offset,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKTopic($data),
        );
    }
}
