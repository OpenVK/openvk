<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Posts, Notes, Videos};

class Wall implements Handler
{
    protected $user;
    protected $posts;
    protected $notes;
    protected $videos;

    public function __construct(?User $user)
    {
        $this->user  = $user;
        $this->posts = new Posts();
        $this->notes = new Notes();
        $this->videos = new Videos();
    }

    public function getPost(int $id, callable $resolve, callable $reject): void
    {
        $post = $this->posts->get($id);
        if (!$post || $post->isDeleted()) {
            $reject(53, "No post with id=$id");
        }

        if ($post->getSuggestionType() != 0) {
            $reject(25, "Can't get suggested post");
        }

        if (!$post->canBeViewedBy($this->user)) {
            $reject(12, "Access denied");
        }

        $res = (object) [];
        $res->id     = $post->getId();
        $res->wall   = $post->getTargetWall();
        $res->author = (($owner = $post->getOwner())) instanceof User
                       ? ($owner->getId())
                       : ($owner->getId() * -1);

        if ($post->isSigned()) {
            $res->signedOffBy = $post->getOwnerPost();
        }

        $res->pinned    = $post->isPinned();
        $res->sponsored = $post->isAd();
        $res->nsfw      = $post->isExplicit();
        $res->text      = $post->getText();

        $res->likes = [
            "count"   => $post->getLikesCount(),
            "hasLike" => $post->hasLikeFrom($this->user),
            "likedBy" => [],
        ];
        foreach ($post->getLikers() as $liker) {
            $res->likes["likedBy"][] = [
                "id"     => $liker->getId(),
                "url"    => $liker->getURL(),
                "name"   => $liker->getCanonicalName(),
                "avatar" => $liker->getAvatarURL(),
            ];
        }

        $res->created = (string) $post->getPublicationTime();
        $res->canPin  = $post->canBePinnedBy($this->user);
        $res->canEdit = $res->canDelete = $post->canBeDeletedBy($this->user);

        $resolve((array) $res);
    }

    public function newStatus(string $text, callable $resolve, callable $reject): void
    {
        $post = new Post();
        $post->setOwner($this->user->getId());
        $post->setWall($this->user->getId());
        $post->setCreated(time());
        $post->setContent($text);
        $post->setAnonymous(false);
        $post->setFlags(0);
        $post->setNsfw(false);
        $post->save();

        $resolve($post->getId());
    }
}
