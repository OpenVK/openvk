<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\{Comment, Notifications\MentionNotification, Photo, Video, User, Topic, Post};
use openvk\Web\Models\Entities\Notifications\CommentNotification;
use openvk\Web\Models\Repositories\{Comments, Clubs, Videos, Photos, Audios};
use Nette\InvalidStateException as ISE;

final class CommentPresenter extends OpenVKPresenter
{
    protected $presenterName = "comment";
    private $models = [
        "posts"   => "openvk\\Web\\Models\\Repositories\\Posts",
        "photos"  => "openvk\\Web\\Models\\Repositories\\Photos",
        "videos"  => "openvk\\Web\\Models\\Repositories\\Videos",
        "notes"   => "openvk\\Web\\Models\\Repositories\\Notes",
        "topics"  => "openvk\\Web\\Models\\Repositories\\Topics",
    ];

    public function renderLike(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $comment = (new Comments())->get($id);
        if (!$comment || $comment->isDeleted()) {
            $this->notFound();
        }

        if ($comment->getTarget() instanceof Post && $comment->getTarget()->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if (!is_null($this->user)) {
            $comment->toggleLike($this->user->identity);
        }
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->returnJson([
                'success' => true,
            ]);
        }

        $this->redirect($_SERVER["HTTP_REFERER"]);
    }

    public function renderMakeComment(string $repo, int $eId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $repoClass = $this->models[$repo] ?? null;
        if (!$repoClass) {
            chandler_http_panic(400, "Bad Request", "Unexpected $repo.");
        }

        $repo   = new $repoClass();
        $entity = $repo->get($eId);
        if (!$entity) {
            $this->notFound();
        }

        if (!$entity->canBeViewedBy($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if ($entity instanceof Topic && $entity->isClosed()) {
            $this->notFound();
        }

        if ($entity instanceof Post && $entity->getTargetWall() < 0) {
            $club = (new Clubs())->get(abs($entity->getTargetWall()));
        } elseif ($entity instanceof Topic) {
            $club = $entity->getClub();
        }

        if ($entity instanceof Post && $entity->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if ($entity instanceof Topic && $entity->isRestricted() && !$entity->getClub()->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        $flags = 0;
        if ($this->postParam("as_group") === "on" && !is_null($club) && $club->canBeModifiedBy($this->user->identity)) {
            $flags |= 0b10000000;
        }

        $photo = null;
        if ($_FILES["_pic_attachment"]["error"] === UPLOAD_ERR_OK) {
            try {
                $photo = Photo::fastMake($this->user->id, $this->postParam("text"), $_FILES["_pic_attachment"]);
            } catch (ISE $ex) {
                $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_when_publishing_comment_description"));
            }
        }

        $horizontal_attachments = [];
        $vertical_attachments = [];
        if (!empty($this->postParam("horizontal_attachments"))) {
            $horizontal_attachments_array = array_slice(explode(",", $this->postParam("horizontal_attachments")), 0, OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxAttachments"]);
            if (sizeof($horizontal_attachments_array) > 0) {
                $horizontal_attachments = parseAttachments($horizontal_attachments_array, ['photo', 'video']);
            }
        }

        if (!empty($this->postParam("vertical_attachments"))) {
            $vertical_attachments_array = array_slice(explode(",", $this->postParam("vertical_attachments")), 0, OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxAttachments"]);
            if (sizeof($vertical_attachments_array) > 0) {
                $vertical_attachments = parseAttachments($vertical_attachments_array, ['audio', 'note', 'doc']);
            }
        }

        if (empty($this->postParam("text")) && sizeof($horizontal_attachments) < 1 && sizeof($vertical_attachments) < 1) {
            $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_comment_empty"));
        }

        try {
            $comment = new Comment();
            $comment->setOwner($this->user->id);
            $comment->setModel(get_class($entity));
            $comment->setTarget($entity->getId());
            $comment->setContent($this->postParam("text"));
            $comment->setCreated(time());
            $comment->setFlags($flags);
            $comment->save();
        } catch (\LengthException $ex) {
            $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_comment_too_big"));
        }

        foreach ($horizontal_attachments as $horizontal_attachment) {
            if (!$horizontal_attachment || $horizontal_attachment->isDeleted() || !$horizontal_attachment->canBeViewedBy($this->user->identity)) {
                continue;
            }

            $comment->attach($horizontal_attachment);
        }

        foreach ($vertical_attachments as $vertical_attachment) {
            if (!$vertical_attachment || $vertical_attachment->isDeleted() || !$vertical_attachment->canBeViewedBy($this->user->identity)) {
                continue;
            }

            $comment->attach($vertical_attachment);
        }

        if ($entity->getOwner()->getId() !== $this->user->identity->getId()) {
            if (($owner = $entity->getOwner()) instanceof User) {
                (new CommentNotification($owner, $comment, $entity, $this->user->identity))->emit();
            }
        }

        $excludeMentions = [$this->user->identity->getId()];
        if (($owner = $entity->getOwner()) instanceof User) {
            $excludeMentions[] = $owner->getId();
        }

        $mentions = iterator_to_array($comment->resolveMentions($excludeMentions));
        foreach ($mentions as $mentionee) {
            if ($mentionee instanceof User) {
                (new MentionNotification($mentionee, $entity, $comment->getOwner(), strip_tags($comment->getText())))->emit();
            }
        }

        $this->flashFail("succ", tr("comment_is_added"), tr("comment_is_added_desc"));
    }

    public function renderDeleteComment(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $comment = (new Comments())->get($id);
        if (!$comment) {
            $this->notFound();
        }
        if (!$comment->canBeDeletedBy($this->user->identity)) {
            $this->throwError(403, "Forbidden", tr("error_access_denied"));
        }
        if ($comment->getTarget() instanceof Post && $comment->getTarget()->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        $comment->delete();
        $this->flashFail(
            "succ",
            tr("success"),
            tr("comment_will_not_appear")
        );
    }
}
