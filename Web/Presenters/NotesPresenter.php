<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\{Clubs, Users, Notes};
use openvk\Web\Models\Entities\{User, Note};

final class NotesPresenter extends OpenVKPresenter
{
    private $notes;
    protected $presenterName = "notes";

    public function __construct(Notes $notes)
    {
        $this->notes = $notes;

        parent::__construct();
    }

    public function renderList(int $owner): void
    {
        $this->template->page  = (int) ($this->queryParam("p") ?? 1);

        if ($owner > 0) {
            $user = (new Users())->get($owner);

            if (!$user) {
                $this->notFound();
            }
            if (!$user->getPrivacyPermission('notes.read', $this->user->identity ?? null)) {
                $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
            }

            $this->template->notes = $this->notes->getUserNotes($user, $this->template->page);
            $this->template->count = $this->notes->getUserNotesCount($user);
            $this->template->owner = $user;
        } else {
            $club = (new Clubs())->get($owner * -1);

            if (!$club || $club->isBanned() || is_null($this->user) || !$club->canBeModifiedBy($this->user->identity) || $club->isWikiPagesDisabledEnforced()) {
                $this->notFound();
            }

            $this->template->notes = $this->notes->getClubNotes($club, $this->template->page);
            $this->template->count = $this->notes->getClubNotesCount($club);
            $this->template->owner = $club;
            $this->template->canCreateNotesInThisGroup = $club->canCreateNote($this->user->identity);
        }
    }

    public function renderView(int $owner, int $note_id): void
    {
        $note = $this->notes->getNoteById($owner, $note_id);
        if (!$note || $note->isDeleted()) {
            $this->notFound();
        }

        if ($note->getOwner() instanceof User) {
            if ($note->getOwner()->getId() !== $owner) {
                $this->notFound();
            }
            if (!$note->getOwner()->getPrivacyPermission('notes.read', $this->user->identity ?? null)) {
                $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
            }
            if (!$note->canBeViewedBy($this->user->identity)) {
                $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
            }
        } elseif ($note->getOwner()->isBanned() || $note->getOwner()->isWikiPagesDisabledEnforced()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        $this->template->cCount   = $note->getCommentsCount();
        $this->template->cPage    = (int) ($this->queryParam("p") ?? 1);
        $this->template->comments = iterator_to_array($note->getComments($this->template->cPage));
        $this->template->note     = $note;
    }

    public function renderPreView(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        if (empty($this->postParam("html")) || empty($this->postParam("title"))) {
            header("HTTP/1.1 400 Bad Request");
            exit(tr("note_preview_empty_err"));
        }

        $note = new Note();
        $note->setSource($this->postParam("html"));

        $this->flash("info", tr("note_preview_warn"), tr("note_preview_warn_details"));
        $this->template->title = $this->postParam("title");
        $this->template->html  = $note->getText();
    }

    public function renderCreate(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $id = $this->user->id; #TODO: when ACL'll be done, allow admins to edit users via ?GUID=(chandler guid)

        if (!$id) {
            $this->notFound();
        }

        $owner = $id;

        $group_id = (int) $this->queryParam("gid");

        if ($group_id) {
            $group = (new Clubs())->get($group_id);
            if (!$group || $group->isBanned()) {
                $this->notFound();
            }

            if (!$group->canCreateNote($this->user->identity) || $group->isWikiPagesDisabledEnforced()) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }

            $owner = $group->getId() * -1;
            $this->template->club = $group;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (empty($this->postParam("name"))) {
                $this->flashFail("err", tr("error"), tr("error_segmentation"));
            }

            $note = new Note();
            $note->setOwner($owner);
            $note->setCreated(time());
            $note->setName($this->postParam("name"));
            $note->setSource($this->postParam("html"));
            $note->setEdited(time());
            $note->save();

            $this->redirect("/note" . $owner . "_" . $note->getVirtualId());
        }
    }

    public function renderEdit(int $owner, int $note_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $note = $this->notes->getNoteById($owner, $note_id);

        if (!$note || $note->isDeleted()) {
            $this->notFound();
        }

        if ($note->getOwner() instanceof User) {
            if ($note->getOwner()->getId() !== $owner) {
                $this->notFound();
            }

            if (is_null($this->user) || !$note->canBeModifiedBy($this->user->identity)) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
            }
        } elseif (is_null($this->user) || !$note->getOwner()->canBeModifiedBy($this->user->identity) || $note->getOwner()->isWikiPagesDisabledEnforced()) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->note = $note;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (empty($this->postParam("name"))) {
                $this->flashFail("err", tr("error"), tr("error_segmentation"));
            }

            $note->setName($this->postParam("name"));
            $note->setSource($this->postParam("html"));
            $note->setCached_Content(null);
            $note->setEdited(time());
            $note->save();

            $this->redirect("/note" . $note->getOwner()->getId() * ($note->getOwner() instanceof User ? 1 : -1) . "_" . $note->getVirtualId());
        }
    }

    public function renderDelete(int $owner, int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $note = $this->notes->getNoteById($owner, $id);
        if (!$note || $note->isDeleted()) {
            $this->notFound();
        }

        $oid = $note->getOwner()->getId() * ($note->getOwner() instanceof User ? 1 : -1);

        if ($oid > 0) {
            if ($note->getOwner()->getId() . "_" . $note->getId() !== $owner . "_" . $id) {
                $this->notFound();
            }
            if (is_null($this->user) || !$note->canBeModifiedBy($this->user->identity)) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
            }
        } else {
            if (is_null($this->user) || !$note->getOwner()->canBeModifiedBy($this->user->identity) || $note->getOwner()->isWikiPagesDisabledEnforced()) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
            }

            if ($note->getId() === $note->getOwner()->getMainNoteId()) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("wiki_pages_cant_remove_main", "/club" . $note->getOwner()->getId() . "/notes"));
            }
        }

        $name = $note->getName();
        $note->delete();
        $this->flash("succ", tr("note_is_deleted"), tr("note_x_is_now_deleted", $name));
        $this->redirect("/notes" . $oid);
    }
}
