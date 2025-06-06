<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{Note};

class Comment extends Post
{
    protected $tableName = "comments";
    protected $upperNodeReferenceColumnName = "owner";

    public function getPrettyId(): string
    {
        return (string) $this->getRecord()->id;
    }

    public function getVirtualId(): int
    {
        return 0;
    }

    public function getTarget(): ?Postable
    {
        $entityClassName = $this->getRecord()->model;
        $repoClassName   = str_replace("Entities", "Repositories", $entityClassName) . "s";
        $entity          = (new $repoClassName())->get($this->getRecord()->target);

        return $entity;
    }

    public function getPageURL(): string
    {
        return '#';
    }

    /**
     * May return fake owner (group), if flags are [1, (*)]
     *
     * @param bool $honourFlags - check flags
     */
    public function getOwner(bool $honourFlags = true, bool $real = false): RowModel
    {
        if ($honourFlags && $this->isPostedOnBehalfOfGroup()) {
            if ($this->getTarget() instanceof Post) {
                return (new Clubs())->get(abs($this->getTarget()->getTargetWall()));
            }

            if ($this->getTarget() instanceof Topic) {
                return $this->getTarget()->getClub();
            }
        }

        return parent::getOwner($honourFlags, $real);
    }

    public function canBeDeletedBy(User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $this->getOwner()->getId() == $user->getId() ||
               $this->getTarget()->getOwner()->getId() == $user->getId() ||
               $this->getTarget() instanceof Post && $this->getTarget()->getTargetWall() < 0 && (new Clubs())->get(abs($this->getTarget()->getTargetWall()))->canBeModifiedBy($user) ||
               $this->getTarget() instanceof Topic && $this->getTarget()->canBeModifiedBy($user);
    }

    public function toVkApiStruct(?User $user = null, bool $need_likes = false, bool $extended = false, ?Note $note = null): object
    {
        $res = (object) [];

        $res->id            = $this->getId();
        $res->from_id       = $this->getOwner()->getId();
        $res->date          = $this->getPublicationTime()->timestamp();
        $res->text          = $this->getText(false);
        $res->attachments   = [];
        $res->parents_stack = [];

        if (!is_null($note)) {
            $res->uid       = $this->getOwner()->getId();
            $res->nid       = $note->getId();
            $res->oid       = $note->getOwner()->getId();
        }

        foreach ($this->getChildren() as $attachment) {
            if ($attachment->isDeleted()) {
                continue;
            }

            if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                $res->attachments[] = $attachment->toVkApiStruct();
            } elseif ($attachment instanceof \openvk\Web\Models\Entities\Video) {
                $res->attachments[] = $attachment->toVkApiStruct($this->getUser());
            }
        }

        if ($need_likes) {
            $res->count      = $this->getLikesCount();
            $res->user_likes = (int) $this->hasLikeFrom($user);
            $res->can_like   = 1;
        }
        return $res;
    }

    public function getURL(): string
    {
        return "/wall" . $this->getTarget()->getPrettyId() . "#_comment" . $this->getId();
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted() || $this->getTarget()->isDeleted()) {
            return false;
        }

        return $this->getTarget()->canBeViewedBy($user);
    }

    public function isFromPostAuthor($target = null)
    {
        if (!$target) {
            $target = $this->getTarget();
        }

        $target_owner = $target->getOwner();
        $comment_owner = $this->getOwner();

        if ($target_owner->getRealId() === $comment_owner->getRealId()) {
            return true;
        }

        # TODO: make it work with signer_id

        return false;
    }

    public function toNotifApiStruct()
    {
        $res = (object) [];

        $res->id       = $this->getId();
        $res->owner_id = $this->getOwner()->getId();
        $res->date     = $this->getPublicationTime()->timestamp();
        $res->text     = $this->getText(false);
        $res->post     = null; # todo

        return $res;
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $user->getId() == $this->getOwner(false)->getId();
    }

    public function getTargetURL(): string
    {
        $target = $this->getTarget();
        $target_name = 'wall';

        if (!$target) {
            return '/404';
        }

        switch (get_class($target)) {
            case 'openvk\Web\Models\Entities\Note':
                $target_name = 'note';
                break;
            case 'openvk\Web\Models\Entities\Photo':
                $target_name = 'photo';
                break;
            case 'openvk\Web\Models\Entities\Video':
                $target_name = 'video';
                break;
            case 'openvk\Web\Models\Entities\Topic':
                $target_name = 'topic';
                break;
        }

        return $target_name . $target->getPrettyId();
    }
}
