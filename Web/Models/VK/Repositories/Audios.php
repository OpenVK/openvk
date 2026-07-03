<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Audio as VKAudio;
use openvk\Web\Models\VK\Entities\Playlist as VKPlaylist;
use openvk\Web\Models\VK\Entities\User as VKUser;
use openvk\Web\Models\VK\Util\VKEntityStream;

class Audios
{
    private static array $cache = [];

    public function get(int $ownerId = 0, int $count = 50, int $offset = 0): array
    {
        if ($ownerId === 0) {
            throw new \RuntimeException("VK API: audio.get requires owner_id.");
        }

        try {
            $response = VKAPIClient::i()->call("audio.get", [
                "owner_id" => $ownerId,
                "count"    => min($count, 200),
                "offset"   => $offset,
            ]);
        } catch (\Throwable) {
            return ["items" => [], "count" => 0];
        }

        $audios = [];
        foreach ($response["items"] ?? [] as $item) {
            $audios[] = new VKAudio($item);
        }

        return $audios;
    }

    public function getByOwnerAndVID(int $owner, int $vId): ?VKAudio
    {
        $cacheKey = "{$owner}_{$vId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("audio.getById", [
                "audios" => "{$owner}_{$vId}",
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKAudio($response[0]);
    }

    public function find(string $query = "", array $params = [], array $order = ["type" => "id", "invert" => false]): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query): array {
                try {
                    return VKAPIClient::i()->call("audio.search", [
                        "q"      => $query,
                        "offset" => $offset,
                        "count"  => $count,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }
            },
            fn(array $data): VKAudio => new VKAudio($data)
        );
    }

    public function getByUser(VKUser $user, int $page = 1, ?int $perPage = 10): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($user): array {
                try {
                    return VKAPIClient::i()->call("audio.get", [
                        "owner_id" => $user->getId(),
                        "offset"   => $offset,
                        "count"    => $count,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }
            },
            fn(array $data): VKAudio => new VKAudio($data)
        );
    }

    public function getRandomThreeAudiosByEntityId(int $entityId): array
    {
        try {
            $response = VKAPIClient::i()->call("audio.get", [
                "owner_id" => $entityId,
                "count"    => 3,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $audios = [];
        foreach ($response["items"] ?? [] as $item) {
            $audios[] = new VKAudio($item);
        }

        return $audios;
    }

    public function getUserCollectionSize($user): int
    {
        $id = is_object($user) ? (method_exists($user, 'getId') ? $user->getId() : 0) : (int) $user;
        if (!$id) return 0;

        try {
            $response = VKAPIClient::i()->call("audio.get", [
                "owner_id" => $id,
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return $response["count"] ?? 0;
    }

    public function getClubCollectionSize($user): int
    {
        return $this->getUserCollectionSize($user);
    }

    /* ===== Playlist methods ===== */

    public function getPlaylist(int $id): ?VKPlaylist
    {
        return null;
    }

    public function getPlaylistByOwnerAndVID(int $ownerId, int $playlistId): ?VKPlaylist
    {
        $cacheKey = "pl_{$ownerId}_{$playlistId}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $response = VKAPIClient::i()->call("audio.getPlaylistById", [
                "owner_id"    => $ownerId,
                "playlist_id" => $playlistId,
            ]);
        } catch (\Throwable) {
            return self::$cache[$cacheKey] = null;
        }

        if (empty($response)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = new VKPlaylist($response);
    }

    public function getPlaylistsByUser(VKUser $user, int $page = 1, ?int $perPage = null): VKEntityStream
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $userId = $user->getId();

        return new VKEntityStream(
            function(int $offset, int $count) use ($userId) {
                try {
                    $response = VKAPIClient::i()->call("audio.getPlaylists", [
                        "owner_id" => $userId,
                        "count"    => min($count, 200),
                        "offset"   => $offset,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKPlaylist($data)
        );
    }

    public function getUserPlaylistsCount(VKUser $user): int
    {
        try {
            $response = VKAPIClient::i()->call("audio.getPlaylists", [
                "owner_id" => $user->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return $response["count"] ?? 0;
    }

    public function getPlaylistsByClub($club, int $page = 1, ?int $perPage = null): VKEntityStream
    {
        $clubId = is_object($club) ? $club->getId() : (int) $club;
        $vkUser = new VKUser(["id" => -$clubId]);

        return $this->getPlaylistsByUser($vkUser, $page, $perPage);
    }

    public function getClubPlaylistsCount($club): int
    {
        $clubId = is_object($club) ? $club->getId() : (int) $club;
        $vkUser = new VKUser(["id" => -$clubId]);

        return $this->getUserPlaylistsCount($vkUser);
    }

    public function findPlaylists(string $query = "", array $params = [], array $order = ['type' => 'id', 'invert' => false]): VKEntityStream
    {
        return new VKEntityStream(
            function(int $offset, int $count) use ($query) {
                try {
                    $response = VKAPIClient::i()->call("audio.searchPlaylists", [
                        "q"      => $query,
                        "count"  => min($count, 200),
                        "offset" => $offset,
                    ]);
                } catch (\Throwable) {
                    return ["items" => [], "count" => 0];
                }

                return ["items" => $response["items"] ?? [], "count" => $response["count"] ?? 0];
            },
            fn(array $data) => new VKPlaylist($data)
        );
    }
}
