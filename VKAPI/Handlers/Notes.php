<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Notes as NotesRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Entities\{Note, Comment};

final class Notes extends VKAPIRequestHandler
{
    public function add(string $title, string $text)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($title)) {
            $this->fail(100, "Required parameter 'title' missing.");
        }

        $note = new Note();

        $note->setOwner($this->getUser()->getId());
        $note->setCreated(time());
        $note->setName($title);
        $note->setSource($text);
        $note->setEdited(time());

        $note->save();

        return $note->getVirtualId();
    }

    public function createComment(int $note_id, int $owner_id, string $message, string $attachments = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message)) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        $note = (new NotesRepo())->getNoteById($owner_id, $note_id);

        if (!$note) {
            $this->fail(15, "Access denied");
        }

        if ($note->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if ($note->getOwner()->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!$note->getOwner()->getPrivacyPermission('notes.read', $this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $comment = new Comment();
        $comment->setOwner($this->getUser()->getId());
        $comment->setModel(get_class($note));
        $comment->setTarget($note->getId());
        $comment->setContent($message);
        $comment->setCreated(time());
        $comment->save();

        return $comment->getId();
    }

    public function edit(string $note_id, string $title = "", string $text = "", int $privacy = 0, int $comment_privacy = 0, string $privacy_view  = "", string $privacy_comment  = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $note = (new NotesRepo())->getNoteById($this->getUser()->getId(), (int) $note_id);

        if (!$note) {
            $this->fail(15, "Access denied");
        }

        if ($note->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->canBeModifiedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        !empty($title) ? $note->setName($title) : null;
        !empty($text) ? $note->setSource($text) : null;

        $note->setCached_Content(null);
        $note->setEdited(time());
        $note->save();

        return 1;
    }

    public function get(int $user_id, string $note_ids = "", int $offset = 0, int $count = 10, int $sort = 0)
    {
        $this->requireUser();

        $user = (new UsersRepo())->get($user_id);

        if (!$user || $user->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$user->getPrivacyPermission('notes.read', $this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!$user->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $notes_return_object = (object) [
            "count" => 0,
            "items" => [],
        ];

        if (empty($note_ids)) {
            $notes_return_object->count = (new NotesRepo())->getUserNotesCount($user);

            $notes = array_slice(iterator_to_array((new NotesRepo())->getUserNotes($user, 1, $count + $offset, $sort == 0 ? "ASC" : "DESC")), $offset);

            foreach ($notes as $note) {
                if ($note->isDeleted()) {
                    continue;
                }

                $notes_return_object->items[] = $note->toVkApiStruct();
            }
        } else {
            $notes_splitted = explode(',', $note_ids);

            foreach ($notes_splitted as $note_id) {
                $note = (new NotesRepo())->getNoteById($user_id, $note_id);

                if ($note && !$note->isDeleted()) {
                    $notes_return_object->items[] = $note->toVkApiStruct();
                }
            }
        }

        return $notes_return_object;
    }

    public function getById(int $note_id, int $owner_id, bool $need_wiki = false)
    {
        $this->requireUser();

        $note = (new NotesRepo())->getNoteById($owner_id, $note_id);

        if (!$note) {
            $this->fail(15, "Access denied");
        }

        if ($note->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->getOwner() || $note->getOwner()->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->getOwner()->getPrivacyPermission('notes.read', $this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!$note->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        return $note->toVkApiStruct();
    }

    public function getComments(int $note_id, int $owner_id, int $sort = 1, int $offset = 0, int $count = 100)
    {
        $this->requireUser();

        $note = (new NotesRepo())->getNoteById($owner_id, $note_id);

        if (!$note) {
            $this->fail(15, "Access denied");
        }

        if ($note->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->getOwner()) {
            $this->fail(15, "Access denied");
        }

        if (!$note->getOwner()->getPrivacyPermission('notes.read', $this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!$note->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $arr = (object) [
            "count" => $note->getCommentsCount(),
            "comments" => []];
        $comments = array_slice(iterator_to_array($note->getComments(1, $count + $offset)), $offset);

        foreach ($comments as $comment) {
            $arr->comments[] = $comment->toVkApiStruct($this->getUser(), false, false, $note);
        }

        return $arr;
    }
}
