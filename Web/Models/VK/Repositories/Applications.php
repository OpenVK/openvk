<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Repositories;
use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Application;
use openvk\Web\Models\VK\Util\VKEntityStream;

class Applications
{
    public function getByOwner($user, int $page = 1, ?int $perPage = null): VKEntityStream
    {
        $ownerId = is_object($user) ? $user->getId() : (int) $user;

        return new VKEntityStream(
            function (int $offset, int $count) use ($ownerId) {
                $response = VKAPIClient::i()->call("apps.get", [
                    "owner_id" => $ownerId,
                    "count"    => max($count, 1),
                    "offset"   => $offset,
                ]);
                return [
                    "items" => $response["items"] ?? [],
                    "count" => $response["count"] ?? 0,
                ];
            },
            fn(array $data) => new Application($data)
        );
    }

    public function find(string $q, array $params, array $order, int $count = 20, int $offset = 0): VKEntityStream
    {
        return new VKEntityStream(
            function (int $offsetParam, int $countParam) use ($count, $offset, $q) {
                $response = VKAPIClient::i()->call("apps.getCatalog", [
                    "q"      => $q,
                    "count"  => min($countParam ?: $count, 100),
                    "offset" => $offsetParam ?: $offset,
                ]);
                return [
                    "items" => $response["items"] ?? [],
                    "count" => $response["count"] ?? 0,
                ];
            },
            fn(array $data) => new Application($data)
        );
    }

    public function get(int $appId): ?Application
    {
        try {
            $response = VKAPIClient::i()->call("apps.get", ["app_id" => $appId]);
        } catch (\Throwable) {
            return null;
        }
        $items = $response["items"] ?? [];
        return !empty($items) ? new Application($items[0]) : null;
    }

    // Stubs for methods used by presenters
    public function getOwnCount($user): int { return 0; }
    public function getInstalled($user, int $page = 1, ?int $perPage = null): array { return []; }
    public function getInstalledCount($user): int { return 0; }
}
