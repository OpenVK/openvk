<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Chat;
use Chandler\Database\DatabaseConnection;

class Chats
{
    private $context;
    private $chats;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->chats = $this->context->table("chats");
    }

    public function get(int $id): ?Chat
    {
        $chat = $this->chats->get($id);
        if (!$chat) {
            return null;
        }

        return new Chat($chat);
    }

    public function getByUser(int $userId, int $page = 1, ?int $perPage = null): \Traversable
    {
        $limit = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $chats = $this->chats->where("JSON_CONTAINS(users, ?)", json_encode($userId))
                             ->order("id DESC")
                             ->limit($limit, $offset);

        foreach ($chats as $chat) {
            yield new Chat($chat);
        }
    }

    public function create(string $type, string $title, int $adminId, array $users): Chat
    {
        $data = [
            'type' => $type,
            'title' => $title,
            'admin_id' => $adminId,
            'users' => json_encode($users),
            'push_settings' => json_encode(['sound' => 1, 'disabled_until' => 0]),
            'photo_50' => null,
            'photo_100' => null,
            'photo_200' => null,
            'left' => 0,
            'kicked' => 0,
        ];

        $chat = $this->chats->insert($data);
        return new Chat($chat);
    }

    public function findByUsers(array $userIds): ?Chat
    {
        // For simplicity, assume chats are unique by users, but in reality, might need better logic
        $chats = $this->chats->where("JSON_CONTAINS(users, ?)", json_encode($userIds[0]));
        foreach ($chats as $chat) {
            $chatUsers = json_decode($chat->users, true);
            if (count($chatUsers) == count($userIds) && !array_diff($chatUsers, $userIds)) {
                return new Chat($chat);
            }
        }
        return null;
    }
}