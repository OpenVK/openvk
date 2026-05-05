<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;

/**
 * Chat entity for multi-user conversations.
 */
class Chat extends RowModel
{
    protected $tableName = "chats";

    /**
     * Get the admin user of the chat.
     */
    public function getAdmin(): ?User
    {
        return (new Users())->get($this->getRecord()->admin_id);
    }

    /**
     * Get the list of users in the chat.
     *
     * @return array<User>
     */
    public function getUsers(): array
    {
        $userIds = json_decode($this->getRecord()->users, true) ?? [];
        $users = [];
        $usersRepo = new Users();
        foreach ($userIds as $id) {
            $user = $usersRepo->get($id);
            if ($user) {
                $users[] = $user;
            }
        }
        return $users;
    }

    /**
     * Get push settings.
     *
     * @return object
     */
    public function getPushSettings(): object
    {
        return json_decode($this->getRecord()->push_settings) ?? (object)['sound' => 1, 'disabled_until' => 0];
    }

    /**
     * Check if the chat is left by the user.
     */
    public function isLeft(): bool
    {
        return (bool) $this->getRecord()->left;
    }

    /**
     * Check if the user is kicked from the chat.
     */
    public function isKicked(): bool
    {
        return (bool) $this->getRecord()->kicked;
    }

    /**
     * Add a user to the chat.
     */
    public function addUser(int $userId): bool
    {
        $users = json_decode($this->getRecord()->users, true) ?? [];
        if (!in_array($userId, $users)) {
            $users[] = $userId;
            $this->stateChanges("users", json_encode($users));
            return true;
        }
        return false;
    }

    /**
     * Remove a user from the chat.
     */
    public function removeUser(int $userId): bool
    {
        $users = json_decode($this->getRecord()->users, true) ?? [];
        $key = array_search($userId, $users);
        if ($key !== false) {
            unset($users[$key]);
            $this->stateChanges("users", json_encode(array_values($users)));
            return true;
        }
        return false;
    }

    /**
     * Update push settings.
     */
    public function updatePushSettings(int $sound, int $disabledUntil): void
    {
        $settings = json_encode(['sound' => $sound, 'disabled_until' => $disabledUntil]);
        $this->stateChanges("push_settings", $settings);
    }
}