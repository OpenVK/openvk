<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\GroupPage;
use openvk\Web\Models\Repositories\{GroupPages, Clubs};

final class PagesPresenter extends OpenVKPresenter
{
    private GroupPages $pages;
    private Clubs $clubs;
    protected $presenterName = "pages";

    public function __construct(GroupPages $pages, Clubs $clubs)
    {
        $this->pages = $pages;
        $this->clubs = $clubs;

        parent::__construct();
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

    private function getPageOrFail(int $clubId, int $virtualId): GroupPage
    {
        $page = $this->pages->getPageById($clubId, $virtualId);
        if (!$page) {
            $this->notFound();
        }

        return $page;
    }

    public function renderList(int $clubId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $this->pages->ensureSingleMain($club);

        $perPage = OPENVK_DEFAULT_PER_PAGE;
        $page    = max(1, (int) ($this->queryParam("p") ?? 1));
        $count   = $this->pages->getClubPagesCount($club);
        $pageCount = max(1, (int) ceil($count / $perPage));
        if ($page > $pageCount) {
            $page = $pageCount;
        }

        $this->template->club  = $club;
        $this->template->pages = $this->pages->getClubPages($club, $page, $perPage);
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

    public function renderCreate(int $clubId): void
    {
        $this->assertUserLoggedIn();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);

        if (!$club->canCreatePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->club = $club;
        $this->template->prefillTitle = $this->queryParam("title") ?? "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();
            $title  = trim((string) $this->postParam("title"));
            $source = (string) ($this->postParam("source") ?? "");

            if ($title === "") {
                $this->flashFail("err", tr("error"), tr("page_no_title"));
            }

            if (mb_strlen($title) > 255) {
                $this->flashFail("err", tr("error"), tr("page_title_too_long"));
            }

            $existing = $this->pages->getByTitle($club, $title);
            if ($existing) {
                $this->flashFail("err", tr("error"), tr("page_title_exists"));
            }

            $isFirst = $this->pages->getClubPagesCount($club) === 0;
            $viewAccess = (int) ($this->postParam("view_access") ?? GroupPage::ACCESS_EVERYONE);
            $editAccess = (int) ($this->postParam("edit_access") ?? GroupPage::ACCESS_ADMINS);
            if ($viewAccess < 0 || $viewAccess > 2 || $editAccess < 0 || $editAccess > 2) {
                $this->flashFail("err", tr("error"), tr("error_segmentation"));
            }

            $page = new GroupPage();
            $page->setGroup($club->getId());
            $page->setOwner($this->user->id);
            $page->setTitle(ovk_proc_strtr($title, 255));
            $page->setSource($source);
            $page->setIs_Main($isFirst ? 1 : 0);
            $page->setView_Access($viewAccess);
            $page->setEdit_Access($editAccess);
            $page->setRevisionEditor($this->user->id);
            $page->save();

            if ($isFirst) {
                $page->makeMain();
            }

            $this->redirect($page->getURL());
        }
    }

    public function renderView(int $clubId, int $virtualId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "view";
    }

    public function renderEdit(int $clubId, int $virtualId): void
    {
        $this->assertUserLoggedIn();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$page->canBeEditedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "edit";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();
            $title  = trim((string) $this->postParam("title"));
            $source = (string) ($this->postParam("source") ?? "");

            if ($title === "") {
                $this->flashFail("err", tr("error"), tr("page_no_title"));
            }

            if (mb_strlen($title) > 255) {
                $this->flashFail("err", tr("error"), tr("page_title_too_long"));
            }

            $existing = $this->pages->getByTitle($club, $title);
            if ($existing && $existing->getId() !== $page->getId()) {
                $this->flashFail("err", tr("error"), tr("page_title_exists"));
            }

            $page->setTitle(ovk_proc_strtr($title, 255));
            $page->setSource($source);
            $page->setRevisionEditor($this->user->id);
            $page->save();

            $this->redirect($page->getURL());
        }
    }

    public function renderDelete(int $clubId, int $virtualId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$page->canBeEditedBy($this->user->identity) && !$club->canManagePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        // Only managers can delete
        if (!$club->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $wasMain = $page->isMain();
        $page->delete();

        if ($wasMain) {
            $pages = iterator_to_array($this->pages->getClubPages($club, 1, 1));
            if (isset($pages[0])) {
                $pages[0]->makeMain();
            }
        }

        $this->flash("succ", tr("page_deleted"), tr("page_deleted_descr"));
        $this->redirect("/pages-" . $club->getId());
    }

    public function renderSetMain(int $clubId, int $virtualId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$club->canManagePages($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $page->makeMain();
        $this->flash("succ", tr("success_action"), tr("page_set_main_succ"));
        $this->redirect("/pages-" . $club->getId());
    }

    public function renderAccess(int $clubId, int $virtualId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$club->canManagePages($this->user->identity) && !$page->canBeEditedBy($this->user->identity)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect($page->getURL() . "/edit");
        }

        $viewAccess = (int) ($this->postParam("view_access") ?? 0);
        $editAccess = (int) ($this->postParam("edit_access") ?? 2);

        if ($viewAccess < 0 || $viewAccess > 2 || $editAccess < 0 || $editAccess > 2) {
            $this->flashFail("err", tr("error"), tr("error_segmentation"));
        }

        $page->setView_Access($viewAccess);
        $page->setEdit_Access($editAccess);
        // Avoid creating a content revision for ACL-only change
        \Chandler\Database\DatabaseConnection::i()->getContext()->table("group_pages")
            ->where("id", $page->getId())
            ->update([
                "view_access" => $viewAccess,
                "edit_access" => $editAccess,
            ]);

        $this->flash("succ", tr("success_action"), tr("page_access_saved"));
        $this->redirect($page->getURL() . "/edit");
    }

    public function renderHistory(int $clubId, int $virtualId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $pageNum = (int) ($this->queryParam("p") ?? 1);
        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->tab  = "history";
        $this->template->revisions = $this->pages->getRevisions($page, $pageNum);
        $this->template->count = $this->pages->getRevisionsCount($page);
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
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $page = $this->getPageOrFail($clubId, $virtualId);

        if (!$page->canBeViewedBy($this->user->identity ?? null)) {
            $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
        }

        $revision = $this->pages->getRevision($page, $revisionId);
        if (!$revision) {
            $this->notFound();
        }

        $this->template->club = $club;
        $this->template->page = $page;
        $this->template->revision = $revision;
        $this->template->tab = "history";
        $this->template->html = GroupPage::renderMarkdown($revision->getSource(), $club);

        if ($_SERVER["REQUEST_METHOD"] === "POST" && $this->postParam("restore") === "1") {
            $this->assertUserLoggedIn();
            $this->willExecuteWriteAction();
            $this->assertNoCSRF();

            if (!$page->canBeEditedBy($this->user->identity)) {
                $this->flashFail("err", tr("error_access_denied_short"), tr("error_access_denied"));
            }

            $page->setTitle($revision->getTitle());
            $page->setSource($revision->getSource());
            $page->setRevisionEditor($this->user->id);
            $page->save();

            $this->flash("succ", tr("success_action"), tr("page_restored"));
            $this->redirect($page->getURL());
        }
    }

    public function renderPreview(): void
    {
        $this->assertUserLoggedIn();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        $clubId = (int) ($this->postParam("club") ?? 0);
        $club = $this->clubs->get($clubId);
        if (!$club || !$club->isPagesEnabled()) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }

        $source = (string) ($this->postParam("source") ?? "");
        header("Content-Type: text/html; charset=utf-8");
        exit(GroupPage::renderMarkdown($source, $club));
    }

    public function renderHelp(int $clubId): void
    {
        $club = $this->getClubOrFail($clubId);
        $this->assertPagesEnabled($club);
        $this->template->club = $club;
    }
}
