<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{Club, Note, User};
use openvk\Web\Models\Repositories\{Users, Notes, Clubs};

final class NotesPresenter extends OpenVKPresenter
{
    private Notes $notes;
    private Clubs $clubs;
    protected $presenterName = "notes";

    public function __construct(Notes $notes, Clubs $clubs)
    {
        $this->notes = $notes;
        $this->clubs = $clubs;

        parent::__construct();
    }

    private function isClubNotesRequest(): bool
    {
        $path = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?: "";

        return (bool) preg_match('#^/note(s)?-#', $path);
    }

    private function getClubOrFail(int $id)
    {
        $club = $this->clubs->get($id);
        if (!$club || $club->isBanned()) {
            $this->notFound();
        }

        return $club;
    }

    private function assertPagesEnabled($club): void
    {
        if (!$club->isPagesEnabled()) {
            $this->notFound();
        }
    }

    private function getClubNoteOrFail(int $clubId, int $virtualId): Note
    {
        $page = $this->notes->getNoteById(-$clubId, $virtualId);
        if (!$page) {
            $this->notFound();
        }

        return $page;
    }

    private function postedName(): string
    {
        $title = trim((string) ($this->postParam("title") ?? ""));
        if ($title !== "") {
            return $title;
        }

        return trim((string) ($this->postParam("name") ?? ""));
    }

    private function postedSource(bool $allowHtmlFormat = true): string
    {
        if ($allowHtmlFormat && $this->postedFormat() === Note::FORMAT_HTML) {
            $html = $this->postParam("html");
            if ($html !== null) {
                return (string) $html;
            }
        }

        $source = $this->postParam("source");
        if ($source !== null) {
            return (string) $source;
        }

        if (!$allowHtmlFormat) {
            return "";
        }

        return (string) ($this->postParam("html") ?? "");
    }

    private function postedFormat(): int
    {
        $format = (int) ($this->postParam("format") ?? Note::FORMAT_MARKDOWN);
        if ($format !== Note::FORMAT_HTML && $format !== Note::FORMAT_MARKDOWN) {
            return Note::FORMAT_MARKDOWN;
        }

        return $format;
    }

    private function postedAccess(string $field, int $default): int
    {
        $value = (int) ($this->postParam($field) ?? $default);
        if ($value < 0 || $value > 2) {
            $this->flashFail("err", tr("error"), tr("error_segmentation"));
        }

        return $value;
    }

    public function renderList(int $owner): void
    {
        if ($this->isClubNotesRequest()) {
            $this->renderClubList($owner);
            return;
        }

        $user = (new Users())->get($owner);
        if (!$user) {
            $this->notFound();
        }
        if (!$user->getPrivacyPermission("notes.read", $this->user->identity ?? null)) {
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
        }

        $this->template->page  = (int) ($this->queryParam("p") ?? 1);
        $this->template->notes = $this->notes->getUserNotes($user, $this->template->page);
        $this->template->count = $this->notes->getUserNotesCount($user);
        $this->template->owner = $user;
    }

    public function renderView(int $owner, int $note_id): void
    {
        if ($this->isClubNotesRequest()) {
            $this->renderClubView($owner, $note_id);
            return;
        }

        $note = $this->notes->getNoteById($owner, $note_id);
        if (!$note || $note->isDeleted()) {
            $this->notFound();
        }
        $noteOwner = $note->getOwner();
        if (!($noteOwner instanceof User) || $noteOwner->getId() !== $owner) {
            $this->notFound();
        }
        if (!$noteOwner->getPrivacyPermission("notes.read", $this->user->identity ?? null)) {
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
        }
        if (!$note->canBeViewedBy($this->user->identity)) {
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
        }

        $this->assignComments($note);
        $this->template->note = $note;
    }

    public function renderCreate(?int $owner = null): void
    {
        $this->assertUserLoggedIn();

        if ($owner !== null && $this->isClubNotesRequest()) {
            $this->renderClubCreate($owner);
            return;
        }

        $id = $this->user->id;
        if (!$id) {
            $this->notFound();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();
            $name = $this->postedName();
            if ($name === "") {
                $this->flashFail("err", tr("error"), tr("page_no_title"));
            }

            $note = new Note();
            $note->setOwner($this->user->id);
            $note->setCreated(time());
            $note->setName(ovk_proc_strtr($name, 255));
            $note->setSource($this->postedSource());
            $note->setFormat($this->postedFormat());
            $note->setComment_Access($this->postedAccess("comment_access", Note::ACCESS_EVERYONE));
            $note->setEdited(time());
            $note->save();

            $this->redirect("/note" . $this->user->id . "_" . $note->getVirtualId());
        }
    }

    public function renderEdit(int $owner, int $note_id): void
    {
        $this->assertUserLoggedIn();

        if ($this->isClubNotesRequest()) {
            $this->renderClubEdit($owner, $note_id);
            return;
        }

        $note = $this->notes->getNoteById($owner, $note_id);
        if (!$note || $note->isDeleted()) {
            $this->notFound();
        }
        if (is_null($this->user->identity) || !$note->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }
        $this->template->note = $note;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();
            $name = $this->postedName();
            if ($name === "") {
                $this->flashFail("err", tr("error"), tr("page_no_title"));
            }

            $note->setName(ovk_proc_strtr($name, 255));
            $note->setSource($this->postedSource());
            $note->setComment_Access($this->postedAccess("comment_access", $note->getCommentAccess()));
            $note->setCached_Content(null);
            $note->setEdited(time());
            $note->save();

            $this->redirect("/note" . $this->user->id . "_" . $note->getVirtualId());
        }
    }

    public function renderDelete(int $owner, int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        if ($this->isClubNotesRequest()) {
            $this->renderClubDelete($owner, $id);
            return;
        }

        $note = $this->notes->get($id);
        if (!$note) {
            $this->notFound();
        }
        if ($note->getOwner()->getId() . "_" . $note->getId() !== $owner . "_" . $id || $note->isDeleted()) {
            $this->notFound();
        }
        if (is_null($this->user->identity) || !$note->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $name = $note->getName();
        $note->delete();
        $this->flash("succ", tr("note_is_deleted"), tr("note_x_is_now_deleted", $name));
        $this->redirect("/notes" . $this->user->id);
    }

    public function renderPreview(): void
    {
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        $source = $this->postedSource();
        $format = $this->postedFormat();
        $clubId = (int) ($this->postParam("club") ?? 0);
        $viewer = $this->user->identity instanceof User ? $this->user->identity : null;

        header("Content-Type: text/html; charset=utf-8");

        if ($clubId > 0) {
            $club = $this->clubs->get($clubId);
            if (!$club || $club->isBanned() || !$club->isPagesEnabled()) {
                header("HTTP/1.1 404 Not Found");
                exit;
            }
            if (!$this->canPreviewClubWiki($club)) {
                header("HTTP/1.1 403 Forbidden");
                exit;
            }

            exit(Note::renderMarkdown($source, $club, null, $viewer));
        }

        if ($format === Note::FORMAT_HTML) {
            $note = new Note();
            $note->setFormat(Note::FORMAT_HTML);
            $note->setSource($source);
            exit($note->getText($viewer));
        }

        exit(Note::renderMarkdown($source, null, $viewer, $viewer));
    }

    public function renderSetMain(int $clubId, int $virtualId): void
    {
        if (!$this->isClubNotesRequest()) {
            $this->notFound();
        }

        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$club->canManagePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $page->makeMain();
        $this->flash("succ", tr("success_action"), tr("page_set_main_succ"));
        $this->redirect("/notes-" . $club->getId());
    }

    public function renderAccess(int $clubId, int $virtualId): void
    {
        if (!$this->isClubNotesRequest()) {
            $this->notFound();
        }

        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$club->canManagePages($this->user->identity) && !$page->canBeEditedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect($page->getURL() . "/edit");
        }

        $viewAccess    = $this->postedAccess("view_access", 0);
        $editAccess    = $this->postedAccess("edit_access", 2);
        $commentAccess = $this->postedAccess("comment_access", 0);

        DatabaseConnection::i()->getContext()->table("notes")
            ->where("id", $page->getId())
            ->update([
                "view_access"    => $viewAccess,
                "edit_access"    => $editAccess,
                "comment_access" => $commentAccess,
            ]);

        $this->flash("succ", tr("success_action"), tr("page_access_saved"));
        $this->redirect($page->getURL() . "/edit");
    }

    public function renderHistory(int $clubId, int $virtualId): void
    {
        if (!$this->isClubNotesRequest()) {
            $this->notFound();
        }

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        if (!$page->keepsRevisions()) {
            $this->notFound();
        }

        $pageNum = (int) ($this->queryParam("p") ?? 1);
        $this->template->_template = "Notes/ClubHistory.latte";
        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "history";
        $this->template->revisions = $this->notes->getRevisions($page, $pageNum);
        $this->template->count = $this->notes->getRevisionsCount($page);
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $pageNum,
            "amount"  => null,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
            "tidy"    => false,
            "atTop"   => false,
        ];
    }

    public function renderRevision(int $clubId, int $virtualId, int $revisionId): void
    {
        if (!$this->isClubNotesRequest()) {
            $this->notFound();
        }

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        if (!$page->keepsRevisions()) {
            $this->notFound();
        }

        $revision = $this->notes->getRevision($page, $revisionId);
        if (!$revision) {
            $this->notFound();
        }

        $this->template->_template = "Notes/ClubRevision.latte";
        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->revision = $revision;
        $this->template->tab = "history";
        $viewer = $this->user->identity instanceof User ? $this->user->identity : null;
        $this->template->html = Note::renderMarkdown($revision->getSource(), $club, null, $viewer);

        if ($_SERVER["REQUEST_METHOD"] === "POST" && $this->postParam("restore") === "1") {
            $this->assertUserLoggedIn();
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();

            if (!$page->canBeEditedBy($this->user->identity)) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
            }

            $page->setName($revision->getTitle());
            $page->setSource($revision->getSource());
            $page->setRevisionEditor($this->user->id);
            $page->save();

            $this->flash("succ", tr("success_action"), tr("page_restored"));
            $this->redirect($page->getURL());
        }
    }

    public function renderHelp(int $clubId): void
    {
        if (!$this->isClubNotesRequest()) {
            $this->notFound();
        }

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $this->template->_template = "Notes/ClubHelp.latte";
        $this->template->club = $club;
    }

    private function canPreviewClubWiki(Club $club): bool
    {
        $user = $this->user->identity;
        if (!$user instanceof User) {
            return false;
        }

        return $club->canCreatePages($user)
            || $club->canManagePages($user)
            || $club->getSubscriptionStatus($user);
    }

    private function assignComments(Note $note): void
    {
        $this->template->cCount   = $note->getCommentsCount();
        $this->template->cPage    = (int) ($this->queryParam("p") ?? 1);
        $this->template->comments = iterator_to_array($note->getComments($this->template->cPage));
    }

    private function renderClubList(int $clubId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $this->notes->ensureSingleMain($club);

        $perPage = OPENVK_DEFAULT_PER_PAGE;
        $page    = max(1, (int) ($this->queryParam("p") ?? 1));
        $count   = $this->notes->getClubNotesCount($club);
        $pageCount = max(1, (int) ceil($count / $perPage));
        if ($page > $pageCount) {
            $page = $pageCount;
        }

        $this->template->_template = "Notes/ClubList.latte";
        $this->template->club  = $club;
        $this->template->pages = $this->notes->getClubNotes($club, $page, $perPage);
        $this->template->count = $count;
        $this->template->page  = $page;
        $this->template->showingFrom = $count === 0 ? 0 : (($page - 1) * $perPage + 1);
        $this->template->showingTo   = min($page * $perPage, $count);
        $this->template->paginatorConf = (object) [
            "count"     => $count,
            "page"      => $page,
            "amount"    => null,
            "perPage"   => $perPage,
            "tidy"      => true,
            "atTop"     => true,
            "space"     => 6,
            "pageCount" => $pageCount,
        ];
        $this->template->bottomPaginatorConf = clone $this->template->paginatorConf;
        $this->template->bottomPaginatorConf->atTop = false;
        $this->template->bottomPaginatorConf->atBottom = true;
        $this->template->bottomPaginatorConf->tidy = false;
        $this->template->bottomPaginatorConf->space = 11;
    }

    private function renderClubCreate(int $clubId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);

        if (!$club->canCreatePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->_template = "Notes/ClubCreate.latte";
        $this->template->club = $club;
        $this->template->prefillTitle = $this->queryParam("title") ?? "";

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $this->willExecuteWriteAction();
        $this->assertNoCSRF();
        $title  = $this->postedName();
        $source = $this->postedSource(false);

        if ($title === "") {
            $this->flashFail("err", tr("error"), tr("page_no_title"));
        }

        if (mb_strlen($title) > 255) {
            $this->flashFail("err", tr("error"), tr("page_title_too_long"));
        }

        $existing = $this->notes->getByTitle(-$club->getId(), $title);
        if ($existing) {
            $this->flashFail("err", tr("error"), tr("page_title_exists"));
        }

        $isFirst = $this->notes->getClubNotesCount($club) === 0;
        $viewAccess    = $this->postedAccess("view_access", Note::ACCESS_EVERYONE);
        $editAccess    = $this->postedAccess("edit_access", Note::ACCESS_ADMINS);
        $commentAccess = $this->postedAccess("comment_access", Note::ACCESS_EVERYONE);
        $keepRevisions = $this->postParam("keep_revisions") === "1" ? 1 : 0;

        $page = new Note();
        $page->setOwner(-$club->getId());
        $page->setCreated_By($this->user->id);
        $page->setName(ovk_proc_strtr($title, 255));
        $page->setSource($source);
        $page->setFormat(Note::FORMAT_MARKDOWN);
        $page->setIs_Main($isFirst ? 1 : 0);
        $page->setView_Access($viewAccess);
        $page->setEdit_Access($editAccess);
        $page->setComment_Access($commentAccess);
        $page->setKeep_Revisions($keepRevisions);
        $page->setRevisionEditor($this->user->id);
        $page->save();

        if ($isFirst) {
            $page->makeMain();
        }

        $this->redirect($page->getURL());
    }

    private function renderClubView(int $clubId, int $virtualId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->assignComments($page);
        $this->template->_template = "Notes/ClubView.latte";
        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "view";
    }

    private function renderClubEdit(int $clubId, int $virtualId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$page->canBeEditedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->_template = "Notes/ClubEdit.latte";
        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "edit";

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $this->willExecuteWriteAction();
        $this->assertNoCSRF();
        $title  = $this->postedName();
        $source = $this->postedSource(false);

        if ($title === "") {
            $this->flashFail("err", tr("error"), tr("page_no_title"));
        }

        if (mb_strlen($title) > 255) {
            $this->flashFail("err", tr("error"), tr("page_title_too_long"));
        }

        $existing = $this->notes->getByTitle(-$club->getId(), $title);
        if ($existing && $existing->getId() !== $page->getId()) {
            $this->flashFail("err", tr("error"), tr("page_title_exists"));
        }

        if ($this->postParam("keep_revisions") !== null) {
            $page->setKeep_Revisions($this->postParam("keep_revisions") === "1" ? 1 : 0);
        }

        $page->setName(ovk_proc_strtr($title, 255));
        $page->setSource($source);
        $page->setRevisionEditor($this->user->id);
        $page->save();

        $this->redirect($page->getURL());
    }

    private function renderClubDelete(int $clubId, int $virtualId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getClubNoteOrFail($clubId, $virtualId);

        if (!$page->canBeEditedBy($this->user->identity) && !$club->canManagePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        if (!$club->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $wasMain = $page->isMain();
        $page->delete();

        if ($wasMain) {
            $pages = iterator_to_array($this->notes->getClubNotes($club, 1, 1));
            if (isset($pages[0])) {
                $pages[0]->makeMain();
            }
        }

        $this->flash("succ", tr("page_deleted"), tr("page_deleted_descr"));
        $this->redirect("/notes-" . $club->getId());
    }
}
