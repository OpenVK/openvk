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
    public function get(int $owner_id = 0, string $videos = "", string $fields = "", int $offset = 0, int $count = 30, int $extended = 0, int $video_id = 0): object|array
    {
        # $this->requireUser();

        if (empty($videos) && $video_id > 0) {
            $videos = "{$owner_id}_{$video_id}";
        }

        if (!empty($videos)) {
            $vids = array_unique(explode(',', $videos));

            if (sizeof($vids) > 100) {
                $this->fail(15, "Too many ids given");
            }

            $profiles = [];
            $groups = [];
            $items = [];

            foreach ($vids as $vid) {
                $cleanVid = preg_replace('/^video/', '', trim($vid));
                $id       = explode("_", $cleanVid);

                $video = (new VideosRepo())->getByOwnerAndVID(intval($id[0]), intval($id[1] ?? 0), $id[2] ?? null);
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

            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                return array_merge([count($items)], $items);
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
            if ($owner_id === 0 && $this->getUser()) {
                $owner_id = $this->getUser()->getId();
            }

            if ($owner_id > 0) {
                $user = (new UsersRepo())->get($owner_id);
            } else {
                $user = (new ClubsRepo())->get($owner_id * -1);
            }

            if (!$user || $user->isDeleted()) {
                $this->fail(14, "Invalid user");
            }

            if ($user->getRealId() > 0 && !$user->getPrivacyPermission('videos.read', $this->getUser())) {
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

            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                return array_merge([$videosCount], $items);
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

    public function edit(int $owner_id, int $video_id, ?string $name = null, ?string $desc = null, int $no_comments = 0, int $repeat = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $video = (new VideosRepo())->getByOwnerAndVIDUnsafe($owner_id, $video_id);
        $changes = 0;

        if (!$video || $video->isDeleted() || !$video->canBeModifiedBy($this->getUser())) {
            $this->fail(14, "Access denied");
        }

        if ($name != null) {
            $video->setName($name);
            $changes += 1;
        }

        if ($desc != null) {
            $video->setDescription($desc);
            $changes += 1;
        }

        if ($changes > 0) {
            $video->save();
        }

        return [
            "success" => 1
        ];
    }

    public function delete(int $owner_id, int $video_id, int $target_id = null)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($target_id != null) {
            $this->fail(-40, "Videos cannot be collected at this moment.");
        }

        $video = (new VideosRepo())->getByOwnerAndVIDUnsafe($owner_id, $video_id);

        if (!$video || $video->isDeleted() || !$video->canBeModifiedBy($this->getUser())) {
            $this->fail(14, "Access denied");
        }

        # $video->isolate();
        $video->delete();

        return 1;
    }

    public function search(string $q = '', int $sort = 0, int $offset = 0, int $count = 10, bool $extended = false, string $fields = ''): object|array
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

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return array_merge([$count], $return_items);
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

    public function getUserVideos(int $user_id = 0, int $offset = 0, int $count = 30, int $extended = 0): object|array
    {
        return $this->get($user_id, "", "", $offset, $count, $extended);
    }

    public function getComments(
        int $video_id,
        int $owner_id = 0,
        int $need_likes = 0,
        int $offset = 0,
        int $count = 20,
        string $sort = "asc"
    ): array|object {
        $this->requireUser();

        if ($owner_id === 0) {
            $owner_id = $this->getUser()->getId();
        }

        $video = (new VideosRepo())->getByOwnerAndVID($owner_id, $video_id);
        if (!$video || $video->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: video not found");
        }

        $commentsRepo = new CommentsRepo();
        $comments = $commentsRepo->getCommentsByTarget($video, $offset, $count, $sort === "desc" ? "DESC" : "ASC");
        $totalCount = $commentsRepo->getCommentsCountByTarget($video);

        $formatted = [];
        foreach ($comments as $comment) {
            $owner = $comment->getOwner();
            $oid = $owner->getId();
            if ($owner instanceof Club) {
                $oid *= -1;
            }

            $formatted[] = (object) [
                "id"           => $comment->getId(),
                "cid"          => $comment->getId(),
                "from_id"      => $oid,
                "uid"          => $oid,
                "date"         => $comment->getPublicationTime()->timestamp(),
                "text"         => $comment->getText(false),
                "message"      => $comment->getText(false),
                "reply_to_cid" => $comment->getReplyToId() ?? 0,
                "reply_to_uid" => $comment->getReplyToComment()?->getOwner()->getId() ?? 0,
                "likes"        => (object) [
                    "count"      => $comment->getLikesCount(),
                    "user_likes" => (int) $comment->hasLikeFrom($this->getUser()),
                    "can_like"   => 1,
                ],
            ];
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return array_merge([$totalCount], $formatted);
        }

        return (object) [
            "count" => $totalCount,
            "items" => $formatted,
        ];
    }

    public function createComment(
        int $video_id,
        int $owner_id = 0,
        string $message = "",
        string $text = "",
        int $reply_to_cid = 0,
        int $reply_to_comment = 0
    ): object|int {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($owner_id === 0) {
            $owner_id = $this->getUser()->getId();
        }

        $video = (new VideosRepo())->getByOwnerAndVID($owner_id, $video_id);
        if (!$video || $video->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: video not found");
        }

        $msg = !empty($message) ? $message : $text;
        if (empty($msg)) {
            $this->fail(100, "Required parameter 'message' is missing");
        }

        $replyTo = $reply_to_cid ?: ($reply_to_comment ?: null);

        $comment = new Comment();
        $comment->setOwner($this->getUser()->getId());
        $comment->setModel(get_class($video));
        $comment->setTarget($video->getId());
        $comment->setContent($msg);
        $comment->setCreated(time());
        $comment->setReply_To($replyTo);
        $comment->save();

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return (object) [
                "cid" => $comment->getId(),
            ];
        }

        return (object) [
            "comment_id" => $comment->getId(),
        ];
    }

    public function addComment(
        int $video_id,
        int $owner_id = 0,
        string $message = "",
        string $text = "",
        int $reply_to_cid = 0,
        int $reply_to_comment = 0
    ): object|int {
        return $this->createComment($video_id, $owner_id, $message, $text, $reply_to_cid, $reply_to_comment);
    }

    public function deleteComment(
        int $video_id = 0,
        int $comment_id = 0,
        int $cid = 0,
        int $owner_id = 0
    ): int {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $cId = $comment_id ?: $cid;
        $comment = (new CommentsRepo())->get($cId);
        if ($comment && $comment->canBeDeletedBy($this->getUser())) {
            $comment->delete();
        }

        return 1;
    }
}
