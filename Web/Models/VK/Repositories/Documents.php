<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Document as VKDocument;
use openvk\Web\Models\VK\Util\VKEntityStream;

/**
 * VK-репозиторий документов — имитация openvk\Web\Models\Repositories\Documents.
 */
class Documents
{
    /** @var VKDocument[] */
    private static array $cache = [];

    /**
     * VK API не имеет прямого аналога get(int $id),
     * так как document_id уникален только в пределах владельца.
     * Используйте getDocumentById(int $virtual_id, int $real_id).
     */
    public function get(int $id): ?VKDocument
    {
        throw new \RuntimeException(
            "VK API: docs.getById requires owner_id. Use getDocumentById(virtual_id, real_id).",
        );
    }

    /**
     * Получает документ по virtual_id, real_id и опционально access_key.
     * VK: docs.getById
     */
    public function getDocumentById(int $virtual_id, int $real_id, ?string $access_key = null): ?VKDocument
    {
        $cacheKey = "doc_{$virtual_id}_{$real_id}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("docs.getById", [
                "docs" => "{$virtual_id}_{$real_id}" . ($access_key ? "_{$access_key}" : ""),
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKDocument($response[0]);
    }

    /**
     * Получает документ без проверки access_key.
     * VK: docs.getById
     */
    public function getDocumentByIdUnsafe(int $virtual_id, int $real_id): ?VKDocument
    {
        return $this->getDocumentById($virtual_id, $real_id);
    }

    /**
     * Возвращает документы владельца.
     * VK: docs.get
     */
    public function getDocumentsByOwner(int $owner, int $order = 0, int $type = -1): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($owner, $order, $type) {
                $params = [
                    "owner_id" => $owner,
                    "count"    => min($count, 200),
                    "offset"   => $offset,
                ];

                if ($type > 0) {
                    $params["type"] = $type;
                }

                try {
                    $response = VKAPIClient::i()->call("docs.get", $params);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKDocument($data),
        );
    }

    /**
     * Возвращает типы документов пользователя.
     * Заглушка — VK API не предоставляет отдельного метода.
     *
     * @return array
     */
    public function getTypes(int $owner_id): array
    {
        return [];
    }

    /**
     * Возвращает теги документов пользователя.
     * Заглушка — VK API не предоставляет отдельного метода.
     *
     * @return array
     */
    public function getTags(int $owner_id, ?int $type = 0): array
    {
        return [];
    }

    /**
     * Поиск документов.
     * VK: docs.search
     */
    public function find(string $query, array $params = []): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query) {
                $vkParams = [
                    "q" => $query,
                    "count" => min($count, 200),
                    "offset" => $offset,
                ];

                try {
                    $response = VKAPIClient::i()->call("docs.search", $vkParams);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKDocument($data),
        );
    }
}
