<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Entities\Video as VideoEntity;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Entities\Comment;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;

final class Video extends VKAPIRequestHandler
{
    public function get(int $owner_id = 0, string $videos = "", string $fields = "", int $offset = 0, int $count = 30, int $extended = 0): object
    {
        $this->requireUser();

        if (!empty($videos)) {
            $vids = array_unique(explode(',', $videos));

            if (sizeof($vids) > 100) {
                $this->fail(15, "Too many ids given");
            }

            $profiles = [];
            $groups = [];
            $items = [];

            foreach ($vids as $vid) {
                $id    = explode("_", $vid);

                $video = (new VideosRepo())->getByOwnerAndVID(intval($id[0]), intval($id[1]));
                if ($video && !$video->isDeleted()) {
                    $out_video = $video->getApiStructure($this->getUser())->video;
                    $items[] = $out_video;
                    if ($out_video['owner_id']) {
                        if ($out_video['owner_id'] > 0) {
                            $profiles[] = $out_video['owner_id'];
                        } else {
                            $groups[] = abs($out_video['owner_id']);
                        }
                    }
                }
            }

            if ($extended == 1) {
                $profiles = array_unique($profiles);
                $groups   = array_unique($groups);

                $profilesFormatted = [];
                $groupsFormatted   = [];

                foreach ($profiles as $prof) {
                    $profile = (new UsersRepo())->get($prof);
                    $profilesFormatted[] = $profile->toVkApiStruct($this->getUser(), $fields);
                }

                foreach ($groups as $gr) {
                    $group = (new ClubsRepo())->get($gr);
                    $groupsFormatted[] = $group->toVkApiStruct($this->getUser(), $fields);
                }

                return (object) [
                    "count" => sizeof($items),
                    "items" => $items,
                    "profiles" => $profilesFormatted,
                    "groups" => $groupsFormatted,
                ];
            }

            return (object) [
                "count" => count($items),
                "items" => $items,
            ];
        } else {
            if ($owner_id > 0) {
                $user = (new UsersRepo())->get($owner_id);
            } else {
                $this->fail(1, "Not implemented");
            }

            if (!$user || $user->isDeleted()) {
                $this->fail(14, "Invalid user");
            }

            if (!$user->getPrivacyPermission('videos.read', $this->getUser())) {
                $this->fail(21, "This user chose to hide his videos.");
            }

            $videos = (new VideosRepo())->getByUserLimit($user, $offset, $count);
            $videosCount = (new VideosRepo())->getUserVideosCount($user);

            $items = [];
            $profiles = [];
            $groups = [];
            foreach ($videos as $video) {
                $video   = $video->getApiStructure($this->getUser())->video;
                $items[] = $video;
                if ($video['owner_id']) {
                    if ($video['owner_id'] > 0) {
                        $profiles[] = $video['owner_id'];
                    } else {
                        $groups[] = abs($video['owner_id']);
                    }
                }
            }

            if ($extended == 1) {
                $profiles = array_unique($profiles);
                $groups   = array_unique($groups);

                $profilesFormatted = [];
                $groupsFormatted   = [];

                foreach ($profiles as $prof) {
                    $profile = (new UsersRepo())->get($prof);
                    $profilesFormatted[] = $profile->toVkApiStruct($this->getUser(), $fields);
                }

                foreach ($groups as $gr) {
                    $group = (new ClubsRepo())->get($gr);
                    $groupsFormatted[] = $group->toVkApiStruct($this->getUser(), $fields);
                }

                return (object) [
                    "count" => $videosCount,
                    "items" => $items,
                    "profiles" => $profilesFormatted,
                    "groups" => $groupsFormatted,
                ];
            }

            return (object) [
                "count" => $videosCount,
                "items" => $items,
            ];
        }
    }

    public function search(string $q = '', int $sort = 0, int $offset = 0, int $count = 10, bool $extended = false, string $fields = ''): object
    {
        $this->requireUser();

        $params = [];
        $db_sort = ['type' => 'id', 'invert' => false];
        $videos = (new VideosRepo())->find($q, $params, $db_sort);
        $items  = iterator_to_array($videos->offsetLimit($offset, $count));
        $count  = $videos->size();

        $return_items = [];
        $profiles = [];
        $groups = [];
        foreach ($items as $item) {
            $return_item = $item->getApiStructure($this->getUser());
            $return_item = $return_item->video;
            $return_items[] = $return_item;

            if ($return_item['owner_id']) {
                if ($return_item['owner_id'] > 0) {
                    $profiles[] = $return_item['owner_id'];
                } else {
                    $groups[] = abs($return_item['owner_id']);
                }
            }
        }

        if ($extended) {
            $profiles = array_unique($profiles);
            $groups   = array_unique($groups);

            $profilesFormatted = [];
            $groupsFormatted   = [];

            foreach ($profiles as $prof) {
                $profile = (new UsersRepo())->get($prof);
                $profilesFormatted[] = $profile->toVkApiStruct($this->getUser(), $fields);
            }

            foreach ($groups as $gr) {
                $group = (new ClubsRepo())->get($gr);
                $groupsFormatted[] = $group->toVkApiStruct($this->getUser(), $fields);
            }

            return (object) [
                "count" => $count,
                "items" => $return_items,
                "profiles" => $profilesFormatted,
                "groups" => $groupsFormatted,
            ];
        }

        return (object) [
            "count" => $count,
            "items" => $return_items,
        ];
    }
}
