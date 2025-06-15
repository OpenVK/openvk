<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Entities\Club;

final class Groups extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 6, bool $online = false, string $filter = "groups"): object
    {
        $this->requireUser();

        # InfoApp fix
        if ($filter == "admin" && ($user_id != 0 && $user_id != $this->getUser()->getId())) {
            $this->fail(15, 'Access denied: filter admin is available only for current user');
        }

        $clbs = [];
        if ($user_id == 0) {
            foreach ($this->getUser()->getClubs($offset, $filter == "admin", $count, true) as $club) {
                $clbs[] = $club;
            }
            $clbsCount = $this->getUser()->getClubCount();
        } else {
            $users = new UsersRepo();
            $user  = $users->get($user_id);

            if (is_null($user) || $user->isDeleted()) {
                $this->fail(15, "Access denied");
            }

            if (!$user->getPrivacyPermission('groups.read', $this->getUser())) {
                $this->fail(15, "Access denied: this user chose to hide his groups.");
            }

            foreach ($user->getClubs($offset, $filter == "admin", $count, true) as $club) {
                $clbs[] = $club;
            }

            $clbsCount = $user->getClubCount();
        }

        $rClubs = [];

        $ic = sizeof($clbs);
        if (sizeof($clbs) > $count) {
            $ic = $count;
        }

        if (!empty($clbs)) {
            for ($i = 0; $i < $ic; $i++) {
                $usr = $clbs[$i];
                if (is_null($usr)) {

                } else {
                    $rClubs[$i] = (object) [
                        "id" => $usr->getId(),
                        "name" => $usr->getName(),
                        "screen_name" => $usr->getShortCode(),
                        "is_closed" => false,
                        "can_access_closed" => true,
                    ];

                    $flds = explode(',', $fields);

                    foreach ($flds as $field) {
                        switch ($field) {
                            case "verified":
                                $rClubs[$i]->verified = intval($usr->isVerified());
                                break;
                            case "has_photo":
                                $rClubs[$i]->has_photo = is_null($usr->getAvatarPhoto()) ? 0 : 1;
                                break;
                            case "photo_max_orig":
                                $rClubs[$i]->photo_max_orig = $usr->getAvatarURL();
                                break;
                            case "photo_max":
                                $rClubs[$i]->photo_max = $usr->getAvatarURL("original"); // ORIGINAL ANDREI CHINITEL 🥵🥵🥵🥵
                                break;
                            case "photo_50":
                                $rClubs[$i]->photo_50 = $usr->getAvatarURL();
                                break;
                            case "photo_100":
                                $rClubs[$i]->photo_100 = $usr->getAvatarURL("tiny");
                                break;
                            case "photo_200":
                                $rClubs[$i]->photo_200 = $usr->getAvatarURL("normal");
                                break;
                            case "photo_200_orig":
                                $rClubs[$i]->photo_200_orig = $usr->getAvatarURL("normal");
                                break;
                            case "photo_400_orig":
                                $rClubs[$i]->photo_400_orig = $usr->getAvatarURL("normal");
                                break;
                            case "members_count":
                                $rClubs[$i]->members_count = $usr->getFollowersCount();
                                break;
                            case "can_suggest":
                                $rClubs[$i]->can_suggest = !$usr->canBeModifiedBy($this->getUser()) && $usr->getWallType() == 2;
                                break;
                            case "background":
                                $backgrounds = $usr->getBackDropPictureURLs();
                                $rClubs[$i]->background = $backgrounds;
                                break;
                            case "suggested_count":
                                if ($usr->getWallType() != 2) {
                                    $rClubs[$i]->suggested_count = null;
                                    break;
                                }

                                $rClubs[$i]->suggested_count = $usr->getSuggestedPostsCount($this->getUser());

                                break;
                        }
                    }
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
                $response[$i] = (object) [
                    "id"                => $clb->getId(),
                    "name"              => $clb->getName(),
                    "screen_name"       => $clb->getShortCode() ?? "club" . $clb->getId(),
                    "is_closed"         => false,
                    "type"              => "group",
                    "is_member"         => !is_null($this->getUser()) ? (int) $clb->getSubscriptionStatus($this->getUser()) : 0,
                    "can_access_closed" => true,
                ];

                $flds = explode(',', $fields);

                foreach ($flds as $field) {
                    switch ($field) {
                        case "verified":
                            $response[$i]->verified = intval($clb->isVerified());
                            break;
                        case "has_photo":
                            $response[$i]->has_photo = is_null($clb->getAvatarPhoto()) ? 0 : 1;
                            break;
                        case "photo_max_orig":
                            $response[$i]->photo_max_orig = $clb->getAvatarURL();
                            break;
                        case "photo_max":
                            $response[$i]->photo_max = $clb->getAvatarURL();
                            break;
                        case "photo_50":
                            $response[$i]->photo_50 = $clb->getAvatarURL();
                            break;
                        case "photo_100":
                            $response[$i]->photo_100 = $clb->getAvatarURL("tiny");
                            break;
                        case "photo_200":
                            $response[$i]->photo_200 = $clb->getAvatarURL("normal");
                            break;
                        case "photo_200_orig":
                            $response[$i]->photo_200_orig = $clb->getAvatarURL("normal");
                            break;
                        case "photo_400_orig":
                            $response[$i]->photo_400_orig = $clb->getAvatarURL("normal");
                            break;
                        case "members_count":
                            $response[$i]->members_count = $clb->getFollowersCount();
                            break;
                        case "site":
                            $response[$i]->site = $clb->getWebsite();
                            break;
                        case "description":
                            $response[$i]->description = $clb->getDescription();
                            break;
                        case "can_suggest":
                            $response[$i]->can_suggest = !$clb->canBeModifiedBy($this->getUser()) && $clb->getWallType() == 2;
                            break;
                        case "background":
                            $backgrounds = $clb->getBackDropPictureURLs();
                            $response[$i]->background = $backgrounds;
                            break;
                            # unstandard feild
                        case "suggested_count":
                            if ($clb->getWallType() != 2) {
                                $response[$i]->suggested_count = null;
                                break;
                            }

                            $response[$i]->suggested_count = $clb->getSuggestedPostsCount($this->getUser());
                            break;
                        case "contacts":
                            $contacts = [];
                            $contactTmp = $clb->getManagers(1, true);

                            foreach ($contactTmp as $contact) {
                                $contacts[] = [
                                    "user_id" => $contact->getUser()->getId(),
                                    "desc"    => $contact->getComment(),
                                ];
                            }

                            $response[$i]->contacts = $contacts;
                            break;
                        case "can_post":
                            if (!is_null($this->getUser())) {
                                if ($clb->canBeModifiedBy($this->getUser())) {
                                    $response[$i]->can_post = true;
                                } else {
                                    $response[$i]->can_post = $clb->canPost();
                                }
                            }
                            break;
                    }
                }
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
        $find  = $clubs->find($q);

        foreach ($find->offsetLimit($offset, $count) as $group) {
            $array[] = $group->getId();
        }

        if (!$array || sizeof($array) < 1) {
            return (object) [
                "count" => 0,
                "items" => [],
            ];
        }

        return (object) [
            "count" => $find->size(),
            "items" => $this->getById(implode(',', $array), "", $fields),
        ];
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
