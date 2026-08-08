<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\{GroupPage, GroupPageRevision, Club};
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Chandler\Database\DatabaseConnection;

class GroupPages
{
    private $context;

    private static $cache = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
    }

    private function pages(): Selection
    {
        return $this->context->table("group_pages");
    }

    private function revisions(): Selection
    {
        return $this->context->table("group_page_revisions");
    }

    private function toPage(?ActiveRow $ar): ?GroupPage
    {
        return is_null($ar) ? null : new GroupPage($ar);
    }

    private function toRevision(?ActiveRow $ar): ?GroupPageRevision
    {
        return is_null($ar) ? null : new GroupPageRevision($ar);
    }

    public function get(int $id): ?GroupPage
    {
        return self::$cache[$id] ??= $this->toPage($this->pages()->get($id));
    }

    public function getPageById(int $clubId, int $virtualId): ?GroupPage
    {
        return $this->toPage($this->pages()->where([
            "group"      => $clubId,
            "virtual_id" => $virtualId,
            "deleted"    => 0,
        ])->fetch());
    }

    public function getByTitle(Club $club, string $title): ?GroupPage
    {
        return $this->toPage($this->pages()->where([
            "group"   => $club->getId(),
            "title"   => $title,
            "deleted" => 0,
        ])->fetch());
    }

    public function getMainPage(Club $club): ?GroupPage
    {
        return $this->toPage($this->pages()->where([
            "group"   => $club->getId(),
            "is_main" => 1,
            "deleted" => 0,
        ])->order("created ASC")->fetch());
    }

    public function getClubPages(Club $club, int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $rows = $this->pages()->where([
            "group"   => $club->getId(),
            "deleted" => 0,
        ])->order("is_main DESC, edited DESC, created DESC")->page($page, $perPage);

        foreach ($rows as $row) {
            yield $this->toPage($row);
        }
    }

    public function getClubPagesCount(Club $club): int
    {
        return sizeof($this->pages()->where([
            "group"   => $club->getId(),
            "deleted" => 0,
        ]));
    }

    /**
     * Keep at most one main page per club (earliest created wins).
     */
    public function ensureSingleMain(Club $club): void
    {
        $mains = $this->pages()->where([
            "group"   => $club->getId(),
            "deleted" => 0,
            "is_main" => 1,
        ])->order("created ASC");

        $keepId = null;
        foreach ($mains as $row) {
            if ($keepId === null) {
                $keepId = (int) $row->id;
                continue;
            }

            $row->update(["is_main" => 0]);
            unset(self::$cache[(int) $row->id]);
        }
    }

    public function getRevisions(GroupPage $page, int $pageNum = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $rows = $this->revisions()->where("page", $page->getId())
            ->order("created DESC")
            ->page($pageNum, $perPage);

        foreach ($rows as $row) {
            yield $this->toRevision($row);
        }
    }

    public function getRevisionsCount(GroupPage $page): int
    {
        return sizeof($this->revisions()->where("page", $page->getId()));
    }

    public function getRevision(GroupPage $page, int $revisionId): ?GroupPageRevision
    {
        return $this->toRevision($this->revisions()->where([
            "id"   => $revisionId,
            "page" => $page->getId(),
        ])->fetch());
    }
}
