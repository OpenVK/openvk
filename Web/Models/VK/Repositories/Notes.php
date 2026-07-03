<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Note as VKNote;
use openvk\Web\Models\VK\Entities\User as VKUser;

/**
 * VK-репозиторий заметок — имитация openvk\Web\Models\Repositories\Notes.
 */
class Notes
{
    /** @var VKNote[] */
    private static array $cache = [];

    public function get(int $id): ?VKNote
    {
        throw new \RuntimeException(
            "VK API: notes.getById requires owner_id. Use getNoteById(owner, noteId)."
        );
    }

    /**
     * Загружает заметку по owner и virtual_id.
     * VK: notes.getById
     */
    public function getNoteById(int $owner, int $note): ?VKNote
    {
        $cacheKey = "{$owner}_{$note}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("notes.getById", [
                "owner_id" => $owner,
                "note_id"  => $note,
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKNote($response);
    }

    /**
     * Заметки пользователя.
     * VK: notes.get
     *
     * @return VKNote[]
     */
    public function getUserNotes(VKUser $user, int $page = 1, ?int $perPage = null, string $sort = "DESC"): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        try {
            $response = VKAPIClient::i()->call("notes.get", [
                "owner_id" => $user->getId(),
                "count"    => min($perPage, 100),
                "offset"   => $offset,
                "sort"     => $sort === "DESC" ? 0 : 1,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $notes = [];
        foreach ($response["items"] ?? [] as $item) {
            $notes[] = new VKNote($item);
        }

        return $notes;
    }

    /**
     * Количество заметок пользователя.
     */
    public function getUserNotesCount(VKUser $user): int
    {
        try {
            $response = VKAPIClient::i()->call("notes.get", [
                "owner_id" => $user->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($response["count"] ?? 0);
    }
}
