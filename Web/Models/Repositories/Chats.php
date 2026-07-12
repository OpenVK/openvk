<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Chat;
use Nette\Database\Table\ActiveRow;

class Chats
{
    private $context;
    private $chats;

    private static $cache = [];
    private static $cacheByChatId = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->chats   = $this->context->table("chats");
    }

    private function toChat(?ActiveRow $ar): ?Chat
    {
        return is_null($ar) ? null : new Chat($ar);
    }

    public function get(int $id): ?Chat
    {
        return self::$cache[$id] ??= $this->toChat($this->chats->get($id));
    }

    public function getByChatId(int $chatId): ?Chat
    {
        $row = $this->context->table("chats")
            ->where("chat_id = ?", $chatId)
            ->fetch();

        if (!$row) {
            return null;
        }

        return $this->toChat($row);
    }

    public function create(int $chatId, string $title = "", string $description = "", ?int $photoId = null): Chat
    {
        $row = $this->chats->insert([
            "chat_id"     => $chatId,
            "title"       => $title,
            "description" => $description,
            "photo_id"    => $photoId,
        ]);

        $chat = new Chat($row);
        
        self::$cache[$chat->getId()] = $chat;
        self::$cacheByChatId[$chat->getChatId()] = $chat;

        return $chat;
    }
}