<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Chats;

class Chats implements Handler
{
    protected $user;
    protected $chats;

    public function __construct(?User $user)
    {
        $this->user = $user;
        $this->chats = new Chats();
    }

    public function getChat(int $id, callable $resolve, callable $reject): void
    {
        $chat = $this->chats->get($id);
        if (!$chat) {
            $reject(53, "No chat with id=$id");
        }

        $userIds = array_map(fn($u) => $u->getId(), $chat->getUsers());
        if (!in_array($this->user->getId(), $userIds)) {
            $reject(12, "Access denied");
        }

        $res = (object) [];
        $res->id = $chat->getId();
        $res->type = $chat->getRecord()->type;
        $res->title = $chat->getRecord()->title;
        $res->admin_id = $chat->getRecord()->admin_id;
        $res->users = $userIds;
        $res->push_settings = $chat->getPushSettings();
        $res->photo_50 = $chat->getRecord()->photo_50;
        $res->photo_100 = $chat->getRecord()->photo_100;
        $res->photo_200 = $chat->getRecord()->photo_200;
        $res->left = $chat->isLeft();
        $res->kicked = $chat->isKicked();

        $resolve($res);
    }

    public function createChat(string $title, array $userIds, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(5, "Authorization required");
        }

        $users = array_merge([$this->user->getId()], $userIds);
        $chat = $this->chats->create('chat', $title, $this->user->getId(), $users);

        $resolve((object)['chat_id' => $chat->getId()]);
    }
}