<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Entities\Club;

final class Groups extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 6, bool $online = false, string $filter = "groups", int $extended = 0): object
    {
        $this->requireUser();

        # InfoApp fix
        if ($filter == "admin" && ($user_id != 0 && $user_id != $this->getUser()->getId())) {
            $this->fail(15, 'Access denied: filter admin is available only for current user');
        }

        $clbs = [];
        if ($user_id == 0) {
            foreach ($this->getUser()->getClubs($offset, $filter == "admin", $count, true, true) as $club) {
                $clbs[] = $club;
            }
            $clbsCount = $this->getUser()->getClubCount($filter == "admin", true);
        } else {
            $users = new UsersRepo();
            $user  = $users->get($user_id);

            if (is_null($user) || $user->isDeleted()) {
                $this->fail(15, "Access denied");
            }

            if (!$user->getPrivacyPermission('groups.read', $this->getUser())) {
                $this->fail(260, "Access to the groups list is denied due to the user's privacy settings");
            }

            foreach ($user->getClubs($offset, $filter == "admin", $count, true, true) as $club) {
                $clbs[] = $club;
            }

            $clbsCount = $user->getClubCount($filter == "admin", true);
        }

        $rClubs = [];

        $ic = sizeof($clbs);
        if (sizeof($clbs) > $count) {
            $ic = $count;
        }

        if (!empty($clbs)) {
            for ($i = 0; $i < $ic; $i++) {
                $clb = $clbs[$i];
                if (!is_null($clb)) {
                    $rClubs[$i] = $clb->toVkApiStruct($this->user, $fields . ",photo_50,photo_100,photo_200");
                }
            }
        } else {
            $rClubs = [];
        }

        return (object) [
            "count" => $clbsCount,
            "items" => $rClubs,
        ];
    }

    public function getById(string $group_ids = "", string $group_id = "", string $fields = "", int $offset = 0, int $count = 500): ?array
    {
        /* Both offset and count SHOULD be used only in OpenVK code,
           not in your app or script, since it's not oficially documented by VK */

        $clubs = new ClubsRepo();

        if (empty($group_ids) && !empty($group_id)) {
            $group_ids = $group_id;
        }

        if (empty($group_ids) && empty($group_id)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: group_ids is undefined");
        }

        $clbs = explode(',', $group_ids);
        $response = [];

        $ic = sizeof($clbs);

        if (sizeof($clbs) > $count) {
            $ic = $count;
        }

        $clbs = array_slice($clbs, $offset * $count);


        for ($i = 0; $i < $ic; $i++) {
            if ($i > 500 || $clbs[$i] == 0) {
                break;
            }

            if ($clbs[$i] < 0) {
                $this->fail(100, "ты ошибся чутка, у айди группы убери минус");
            }

            $clb = $clubs->get((int) $clbs[$i]);
            if (is_null($clb)) {
                $response[$i] = (object) [
                    "id"          => intval($clbs[$i]),
                    "name"        => "DELETED",
                    "screen_name" => "club" . intval($clbs[$i]),
                    "type"        => "group",
                    "description" => "This group was deleted or it doesn't exist",
                ];
            } elseif ($clbs[$i] == null) {

            } else {
                $response[$i] = $clb->toVkApiStruct($this->user, $fields . ",photo_50,photo_100,photo_200");
            }
        }

        return $response;
    }

    public function search(string $q, int $offset = 0, int $count = 100, string $fields = "screen_name,is_admin,is_member,is_advertiser,photo_50,photo_100,photo_200")
    {
        if ($count > 100) {
            $this->fail(100, "One of the parameters specified was missing or invalid: count should be less or equal to 100");
        }

        $clubs = new ClubsRepo();

        $array = [];
        $find  = $clubs->find($q, [], ['type' => 'id', 'invert' => false], 1, null, true);

        foreach ($find->offsetLimit($offset, $count) as $group) {
            $array[] = $group->getId();
        }

        if (!$array || sizeof($array) < 1) {
            return $this->generateItems(0, []);
        }

        return $this->generateItems($find->size(), $this->getById(implode(',', $array), "", $fields));
    }

    public function join(int $group_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubsRepo())->get($group_id);

        $isMember = !is_null($this->getUser()) ? (int) $club->getSubscriptionStatus($this->getUser()) : 0;

        if ($isMember == 0) {
            if (\openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->getUser(), "groups.sub")) {
                $this->failTooOften();
            }

            $club->toggleSubscription($this->getUser());
        }

        return 1;
    }

    public function leave(int $group_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubsRepo())->get($group_id);

        $isMember = !is_null($this->getUser()) ? (int) $club->getSubscriptionStatus($this->getUser()) : 0;

        if ($isMember == 1) {
            $club->toggleSubscription($this->getUser());
        }

        return 1;
    }

    public function edit(
        int $group_id,
        string $title = null,
        string $description = null,
        string $screen_name = null,
        string $website = null,
        int    $wall = -1,
        int    $topics = null,
        int    $adminlist = null,
        int    $topicsAboveWall = null,
        int    $hideFromGlobalFeed = null,
        int    $audio = null
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubsRepo())->get($group_id);

        if (!$club) {
            $this->fail(15, "Access denied");
        }

        if (!$club || !$club->canBeModifiedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!empty($screen_name) && !$club->setShortcode($screen_name)) {
            $this->fail(103, "Invalid screen_name");
        }

        !empty($title) ? $club->setName($title) : null;
        !empty($description) ? $club->setAbout($description) : null;
        !empty($screen_name) ? $club->setShortcode($screen_name) : null;
        !empty($website) ? $club->setWebsite((!parse_url($website, PHP_URL_SCHEME) ? "https://" : "") . $website) : null;

        try {
            $wall != -1 ? $club->setWall($wall) : null;
        } catch (\Exception $e) {
            $this->fail(50, "Invalid wall value");
        }

        !empty($topics) ? $club->setEveryone_Can_Create_Topics($topics) : null;
        !empty($adminlist) ? $club->setAdministrators_List_Display($adminlist) : null;
        !empty($topicsAboveWall) ? $club->setDisplay_Topics_Above_Wall($topicsAboveWall) : null;

        if (!$club->isHidingFromGlobalFeedEnforced()) {
            !empty($hideFromGlobalFeed) ? $club->setHide_From_Global_Feed($hideFromGlobalFeed) : null;
        }

        in_array($audio, [0, 1]) ? $club->setEveryone_can_upload_audios($audio) : null;

        try {
            $club->save();
        } catch (\TypeError $e) {
            return 1;
        } catch (\Exception $e) {
            return 0;
        }

        return 1;
    }

    public function getMembers(int $group_id, int $offset = 0, int $count = 10, string $fields = "")
    {
        $this->requireUser();

        $club = (new ClubsRepo())->get($group_id);

        if (!$club || !$club->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $sort_string = "follower ASC";
        $members = array_slice(iterator_to_array($club->getFollowers(1, $count, $sort_string)), $offset, $count);

        $obj = (object) [
            "count" => sizeof($members),
            "items" => [],
        ];

        foreach ($members as $member) {
            $obj->items[] = $member->toVkApiStruct($this->getUser(), $fields);
        }

        return $obj;
    }

    public function getSettings(string $group_id)
    {
        $this->requireUser();

        $club = (new ClubsRepo())->get((int) $group_id);

        if (!$club || !$club->canBeModifiedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $arr = (object) [
            "title"          => $club->getName(),
            "description"    => $club->getDescription(),
            "address"        => $club->getShortcode(),
            "wall"           => $club->getWallType(), # is different from vk values
            "photos"         => 1,
            "video"          => 0,
            "audio"          => $club->isEveryoneCanUploadAudios() ? 1 : 0,
            "docs"           => 1,
            "topics"         => $club->isEveryoneCanCreateTopics() == true ? 1 : 0,
            "website"        => $club->getWebsite(),
        ];

        return $arr;
    }

    public function isMember(string $group_id, int $user_id, int $extended = 0)
    {
        $this->requireUser();

        $input_club = (new ClubsRepo())->get(abs((int) $group_id));
        $input_user = (new UsersRepo())->get(abs((int) $user_id));

        if (!$input_club || !$input_club->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!$input_user || $input_user->isDeleted()) {
            $this->fail(15, "Not found");
        }

        if ($extended == 0) {
            return $input_club->getSubscriptionStatus($input_user) ? 1 : 0;
        } else {
            return (object)
            [
                "member"     => $input_club->getSubscriptionStatus($input_user) ? 1 : 0,
                "request"    => 0,
                "invitation" => 0,
                "can_invite" => 0,
                "can_recall" => 0,
            ];
        }
    }
}
