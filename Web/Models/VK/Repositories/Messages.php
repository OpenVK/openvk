<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Repositories;
use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Util\VKEntityStream;
class Messages
{
    public function get(int $id) { return null; }
    
    public function getConversations(int $count = 20, int $offset = 0): array
    {
        try {
            $response = VKAPIClient::i()->call("messages.getConversations", [
                "count"  => min($count, 200),
                "offset" => $offset,
            ]);
        } catch (\Throwable) { return ["items" => [], "count" => 0, "unread" => 0]; }
        return $response;
    }
    
    public function getHistory(int $peerId, int $count = 20, int $offset = 0): array
    {
        try {
            $response = VKAPIClient::i()->call("messages.getHistory", [
                "peer_id" => $peerId,
                "count"   => min($count, 200),
                "offset"  => $offset,
            ]);
        } catch (\Throwable) { return ["items" => [], "count" => 0]; }
        return $response;
    }
    
    public function getCorrespondencies($user, int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= 10;
        $offset = $perPage * ($page - 1);
        return $this->getConversations($perPage, $offset)["items"] ?? [];
    }
    
    public function getCorrespondenciesCount($user): int
    {
        $conv = $this->getConversations(0, 0);
        return $conv["count"] ?? 0;
    }
}
