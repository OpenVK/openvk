<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Users as UsersRepo;
use Chandler\Database\DatabaseConnection;

final class Friends extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 100): object|array
    {
        $i = 0;
        $offset++;
        $friends = [];

        $users = new UsersRepo();

        $this->requireUser();

        if ($user_id == 0) {
            $user_id = $this->getUser()->getId();
        }

        $user = $users->get($user_id);

        if (!$user || $user->isDeleted()) {
            $this->fail(100, "Invalid user");
        }

        if (!$user->getPrivacyPermission("friends.read", $this->getUser())) {
            $this->fail(15, "Access denied: this user chose to hide his friends.");
        }

        foreach ($user->getFriends($offset, $count) as $friend) {
            $friends[$i] = $friend->getId();
            $i++;
        }

        $response = $friends;

        if (!empty($fields)) {
            $usersApi = new Users($this->getUser());
            $response = $usersApi->get(implode(',', $friends), $fields, 0, $count);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return $response;
        }

        return (object) [
            "count" => $users->get($user_id)->getFriendsCount(),
            "items" => $response,
        ];
    }

    public function getSuggestions(string $filter = "mutual", string $fields = "", int $offset = 0, int $count = 100): object|array
    {
        $this->requireUser();

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return [];
        }

        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    public function getOnline(int $user_id = 0, int $online_mobile = 0): array
    {
        $this->requireUser();

        $targetUser = $user_id > 0 ? (new UsersRepo())->get($user_id) : $this->getUser();
        if (!$targetUser || $targetUser->isDeleted()) {
            $this->fail(100, "Invalid user");
        }

        if (!$targetUser->getPrivacyPermission("friends.read", $this->getUser())) {
            $this->fail(15, "Access denied: this user chose to hide his friends.");
        }

        $online = [];
        foreach ($targetUser->getFriendsOnline(1, 1000) as $friend) {
            $online[] = $friend->getId();
        }

        return $online;
    }

    public function getMutual(int $target_uid, int $source_uid = 0): array
    {
        $this->requireUser();

        $users = new UsersRepo();
        $source = $source_uid > 0 ? $users->get($source_uid) : $this->getUser();
        $target = $users->get($target_uid);

        if (!$source || !$target || $source->isDeleted() || $target->isDeleted()) {
            $this->fail(100, "Invalid user");
        }

        if (!$target->getPrivacyPermission("friends.read", $this->getUser())) {
            $this->fail(15, "Access denied: this user chose to hide his friends.");
        }

        $sourceFriends = [];
        foreach ($source->getFriends(1, 5000) as $f) {
            $sourceFriends[] = $f->getId();
        }

        $targetFriends = [];
        foreach ($target->getFriends(1, 5000) as $f) {
            $targetFriends[] = $f->getId();
        }

        return array_values(array_intersect($sourceFriends, $targetFriends));
    }

    public function search(string $q, int $user_id = 0, string $fields = "", int $offset = 0, int $count = 100): object
    {
        $this->requireUser();

        if ($user_id == 0) {
            $user_id = $this->getUser()->getId();
        }

        $users = new UsersRepo();

        $user = $users->get($user_id);

        if (!$user || $user->isDeleted()) {
            $this->fail(100, "Invalid user");
        }

        if (!$user->getPrivacyPermission("friends.read", $this->getUser())) {
            $this->fail(15, "Access denied: this user chose to hide his friends.");
        }

        $q = mb_strtolower($q ?? "", "UTF-8");

        $query   = "SELECT id FROM\n" . file_get_contents(__DIR__ . "/../../Web/Models/sql/get-friends-search.tsql");
        $countQ  = "SELECT COUNT(*) AS cnt FROM\n" . file_get_contents(__DIR__ . "/../../Web/Models/sql/get-friends-search.tsql");

        $db   = DatabaseConnection::i()->getConnection();
        $like = "%$q%";

        $totalCount = (int) $db->query($countQ, $user_id, $user_id, $like)->fetch()->cnt;

        $query .= "\n LIMIT " . $count . " OFFSET " . $offset;

        $matchingFriends = [];
        $rels = $db->query($query, $user_id, $user_id, $like);
        foreach ($rels as $rel) {
            $matchingFriends[] = (int) $rel->id;
        }

        $response = $matchingFriends;

        if (!empty($fields) && sizeof($matchingFriends) > 0) {
            $usersApi = new Users($this->getUser());
            $response = $usersApi->get(implode(',', $matchingFriends), $fields);
        }

        return (object) [
            "count" => $totalCount,
            "items" => $response,
        ];
    }

    public function getLists(): object
    {
        $this->requireUser();

        return (object) [
            "count" => 0,
            "items" => (array) [],
        ];
    }

    public function deleteList(): int
    {
        $this->requireUser();

        return 1;
    }

    public function edit(): int
    {
        $this->requireUser();

        return 1;
    }

    public function editList(): int
    {
        $this->requireUser();

        return 1;
    }

    public function add(string $user_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $users = new UsersRepo();
        $user  = $users->get(intval($user_id));

        if (is_null($user)) {
            $this->fail(177, "Cannot add this user to friends as user not found");
        } elseif ($user->getId() == $this->getUser()->getId()) {
            $this->fail(174, "Cannot add user himself as friend");
        }

        switch ($user->getSubscriptionStatus($this->getUser())) {
            case 0:
                if (\openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->getUser(), "friends.outgoing_sub")) {
                    $this->failTooOften();
                }

                $user->toggleSubscription($this->getUser());
                return 1;

            case 1:
                $user->toggleSubscription($this->getUser());
                return 2;

            case 3:
                return 2;

            default:
                return 1;
        }
    }

    public function delete(string $user_id): object|int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $users = new UsersRepo();

        $user = $users->get(intval($user_id));

        if (!$user) {
            $this->fail(100, "Invalid user");
        }

        switch ($user->getSubscriptionStatus($this->getUser())) {
            case 3:
                $user->toggleSubscription($this->getUser());
                if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                    return (object) ["success" => 1];
                }
                return 1;

            default:
                $this->fail(15, "Access denied: No friend or friend request found.");
        }
    }

    public function areFriends(string $user_ids): array
    {
        $this->requireUser();

        $users = new UsersRepo();

        $friends = explode(',', $user_ids);

        $response = [];

        for ($i = 0; $i < sizeof($friends); $i++) {
            $friend = $users->get(intval($friends[$i]));

            $friend_status = 0;

            switch ($friend->getSubscriptionStatus($this->getUser())) {
                case 3:
                    $friend_status = 3;
                    break;
                case 0:
                    $friend_status = 0;
                    break;
                case 1:
                    $friend_status = 2;
                    break;
                case 2:
                    $friend_status = 1;
                    break;
            }

            $response[] = (object) [
                "friend_status" => $friend_status,
                "user_id" 		=> $friend->getId(),
            ];
        }

        return $response;
    }

    public function getRequests(
        string $fields = "",
        int $out = 0,
        int $offset = 0,
        int $count = 100,
        int $extended = 0,
        int $suggested = 0,
        int $need_messages = 0,
        int $need_mutual = 0
    ): object|array {
        if ($count >= 1000) {
            $this->fail(100, "One of the required parameters was not passed or is invalid.");
        }

        $this->requireUser();

        $i = 0;
        $offset++;
        $followers = [];

        if ($out == 0 && $suggested == 0) {
            foreach ($this->getUser()->getFollowers($offset, $count) as $follower) {
                $followers[$i] = $follower->getId();
                $i++;
            }
        } elseif ($out == 1) {
            foreach ($this->getUser()->getSubscriptions($offset, $count) as $follower) {
                $followers[$i] = $follower->getId();
                $i++;
            }
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $legacyRequests = [];
            foreach ($followers as $followerId) {
                $reqObj = (object) [
                    "uid"     => $followerId,
                    "user_id" => $followerId,
                ];

                if ($need_messages == 1) {
                    $reqObj->message = "";
                }

                if ($need_mutual == 1) {
                    $mutualFriends = $this->getMutual($followerId);
                    $reqObj->mutual = (object) [
                        "count" => count($mutualFriends),
                        "users" => array_slice($mutualFriends, 0, 5),
                    ];
                }

                $legacyRequests[] = $reqObj;
            }

            return $legacyRequests;
        }

        $response = $followers;
        $usersApi = new Users($this->getUser());

        $response = $usersApi->get(implode(',', $followers), $fields, 0, $count);

        foreach ($response as $user) {
            $user->user_id = $user->id;
        }

        return (object) [
            "count" => $this->getUser()->getFollowersCount(),
            "items" => $response,
        ];
    }
}
