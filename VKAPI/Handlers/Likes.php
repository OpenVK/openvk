<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Repositories\Notes as NotesRepo;
use openvk\Web\Models\Repositories\Audios as AudiosRepo;

final class Likes extends VKAPIRequestHandler
{
    private function getEntity(string $type, int $owner_id, int $item_id)
    {
        switch ($type) {
            case "post":
                $post = (new PostsRepo())->getPostById($owner_id, $item_id);
                return $post;
            case "comment":
                $comment = (new CommentsRepo())->get($item_id);
                return $comment;
            case "video":
                $video = (new VideosRepo())->getByOwnerAndVID($owner_id, $item_id);
                return $video;
            case "photo":
                $photo = (new PhotosRepo())->getByOwnerAndVID($owner_id, $item_id);
                return $photo;
            case "note":
                $note = (new NotesRepo())->getNoteById($owner_id, $item_id);
                return $note;
            case "audio_playlist":
                $audioplaylist = (new AudiosRepo())->getPlaylistByOwnerAndVID($owner_id, $item_id);
                return $audioplaylist;
            default:
                $this->fail(100, "One of the parameters specified was missing or invalid: incorrect type");
        }
    }

    public function add(string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $postable = $this->getEntity($type, $owner_id, $item_id);

        if (is_null($postable) || $postable->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: object not found");
        }

        if (!$postable->canBeViewedBy($this->getUser() ?? null)) {
            $this->fail(2, "Access to postable denied");
        }

        $postable->setLike(true, $this->getUser());

        return (object) [
            "likes" => $postable->getLikesCount(),
        ];
    }

    public function delete(string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $postable = $this->getEntity($type, $owner_id, $item_id);

        if (is_null($postable)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: object not found");
        }

        /*if (!$postable->canBeViewedBy($this->getUser() ?? null)) {
            $this->fail(2, "Access to postable denied");
        }*/

        if (!is_null($postable)) {
            $postable->setLike(false, $this->getUser());

            return (object) [
                "likes" => $postable->getLikesCount(),
            ];
        }
    }

    public function isLiked(int $user_id, string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();

        $user = (new UsersRepo())->get($user_id);

        if (is_null($user) || $user->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: user not found");
        }

        if (!$user->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if ($user->isPrivateLikes()) {
            return (object) [
                "liked"  => 1,
                "copied" => 1,
            ];
        }

        $postable = $this->getEntity($type, $owner_id, $item_id);

        if (is_null($postable) || $postable->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: object not found");
        }

        if (!$postable->canBeViewedBy($this->getUser())) {
            $this->fail(665, "Access to postable denied");
        }

        return (object) [
            "liked"  => (int) $postable->hasLikeFrom($user),
            "copied" => 0,
        ];
    }

    public function getList(string $type, int $owner_id, int $item_id, bool $extended = false, int $offset = 0, int $count = 10, bool $skip_own = false)
    {
        $this->requireUser();

        $object = $this->getEntity($type, $owner_id, $item_id);

        if (!$object || $object->isDeleted()) {
            $this->fail(56, "Invalid postable");
        }

        if (!$object->canBeViewedBy($this->getUser())) {
            $this->fail(665, "Access to postable denied");
        }

        $res = (object) [
            "count" => $object->getLikesCount(),
            "items" => [],
        ];

        $likers = array_slice(iterator_to_array($object->getLikers(1, $offset + $count)), $offset);

        foreach ($likers as $liker) {
            if ($skip_own && $liker->getId() == $this->getUser()->getId()) {
                continue;
            }

            if (!$extended) {
                $res->items[] = $liker->getId();
            } else {
                $res->items[] = $liker->toVkApiStruct(null, 'photo_50,photo_100');
            }
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return array_merge([$res->count], $res->items);
        }

        return $res;
    }
}
