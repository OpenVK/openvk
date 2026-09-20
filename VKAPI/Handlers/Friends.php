<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Friends extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 100): object
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

        $usersApi = new Users($this->getUser());

        if (!is_null($fields)) {
            $response = $usersApi->get(implode(',', $friends), $fields, 0, $count);
        }  # FIXME

        return (object) [
            "count" => $users->get($user_id)->getFriendsCount(),
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

    public function delete(string $user_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $users = new UsersRepo();

        $user = $users->get(intval($user_id));

        switch ($user->getSubscriptionStatus($this->getUser())) {
            case 3:
                $user->toggleSubscription($this->getUser());
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

    public function getRequests(string $fields = "", int $out = 0, int $offset = 0, int $count = 100, int $extended = 0, int $suggested = 0): object
    {
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

    public function getMutual(int $source_uid = 0, int $target_uid = 0, string $target_uids = '', string $order = '', ?int $count = null, int $offset = 0, bool $need_common_count = false): object
    {
        $users = new UsersRepo();

        $this->requireUser();
        if ($source_uid == 0) {
            $source_uid = $this->getUser()->getId();
        }
        $source = $users->get($source_uid);

        if (!$source || $source->isDeleted()) {
            $this->fail(100, "User was deleted or banned");
        }

        $is_one = true;
        $targets = [];

        if ($target_uids != '') {
            $target_uids = explode(',', $target_uids);
            foreach ($target_uids as $index => $target_uid) {
                if (!ctype_digit($target_uid)) {
                    $this->fail(100, "One of the parameters specified was missing or invalid: target_uids[$index] not integer");
                }

                $targets[] = (int) $target_uid;
            }
            $is_one = false;
        } elseif ($target_uid > 0) {
            $targets = [
                $target_uid,
            ];
        } else {
            $this->fail(100, "One of the parameters specified was missing or invalid: target_uid is undefined");
        }

        $responses = [];

        foreach ($targets as $target_uid) {
            $response = [
                'common_friends' => [],
                'target_uid' => $target_uid,
            ];

            $target = $users->get($target_uid);
            if (!$target || $target->isDeleted() || $target_uid == $source_uid) {
                $responses[] = $response;
                continue;
            }

            if (!$target->getPrivacyPermission("friends.read", $this->getUser())) {
                $this->fail(30, "This profile is private");
            }

            $query = $target->getCommonFriendsQuery($source)->order($order == "random" ? "RAND()" : "id ASC");
            if ($count > 0) {
                $query->limit($count, $offset);
            }
            $friends = $query->select('id')->fetchAll();

            $response ['common_friends'] = array_values(array_map(fn($friend) => $friend->id, $friends));
            if ($need_common_count) {
                $response['common_count'] = $query->count();
            }

            $responses[] = $response;
        }

        if ($is_one) {
            return (object) $responses[0];
        }

        return (object) array_values($responses);
    }
}
