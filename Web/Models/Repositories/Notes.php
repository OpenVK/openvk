<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{Note, NoteRevision, User, Club};
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class Notes
{
    private $context;
    private $notes;

    private static $cache = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->notes   = $this->context->table("notes");
    }

    private function table(): Selection
    {
        return $this->context->table("notes");
    }

    private function revisions(): Selection
    {
        return $this->context->table("note_revisions");
    }

    private function toNote(?ActiveRow $ar): ?Note
    {
        return is_null($ar) ? null : new Note($ar);
    }

    private function toRevision(?ActiveRow $ar): ?NoteRevision
    {
        return is_null($ar) ? null : new NoteRevision($ar);
    }

    public function get(int $id): ?Note
    {
        return self::$cache[$id] ??= $this->toNote($this->table()->get($id));
    }

    public function getNoteById(int $owner, int $note): ?Note
    {
        return $this->toNote($this->table()->where([
            "owner"      => $owner,
            "virtual_id" => $note,
            "deleted"    => 0,
        ])->fetch());
    }

    public function getByTitle(int $ownerId, string $title): ?Note
    {
        return $this->toNote($this->table()->where([
            "owner"   => $ownerId,
            "name"    => $title,
            "deleted" => 0,
        ])->fetch());
    }

    public function getUserNotes(User $user, int $page = 1, ?int $perPage = null, string $sort = "DESC"): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        foreach ($this->table()->where("owner", $user->getId())->where("deleted", 0)->order("created $sort")->page($page, $perPage) as $row) {
            yield new Note($row);
        }
    }

    public function getUserNotesCount(User $user): int
    {
        return sizeof($this->table()->where("owner", $user->getId())->where("deleted", 0));
    }

    public function getClubNotes(Club $club, int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $rows = $this->table()->where([
            "owner"   => -$club->getId(),
            "deleted" => 0,
        ])->order("is_main DESC, edited DESC, created DESC")->page($page, $perPage);

        foreach ($rows as $row) {
            yield $this->toNote($row);
        }
    }

    public function getClubNotesCount(Club $club): int
    {
        return sizeof($this->table()->where([
            "owner"   => -$club->getId(),
            "deleted" => 0,
        ]));
    }

    public function getMainNote(Club $club): ?Note
    {
        return $this->toNote($this->table()->where([
            "owner"   => -$club->getId(),
            "is_main" => 1,
            "deleted" => 0,
        ])->order("created ASC")->fetch());
    }

    public function ensureSingleMain(Club $club): void
    {
        $mains = $this->table()->where([
            "owner"   => -$club->getId(),
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

    public function getRevisions(Note $note, int $pageNum = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $rows = $this->revisions()->where("note", $note->getId())
            ->order("created DESC")
            ->page($pageNum, $perPage);

        foreach ($rows as $row) {
            yield $this->toRevision($row);
        }
    }

    public function getRevisionsCount(Note $note): int
    {
        return sizeof($this->revisions()->where("note", $note->getId()));
    }

    public function getRevision(Note $note, int $revisionId): ?NoteRevision
    {
        return $this->toRevision($this->revisions()->where([
            "id"   => $revisionId,
            "note" => $note->getId(),
        ])->fetch());
    }

    public function pruneRevisions(Note $note, int $keep = 50): void
    {
        $keepIds = [];
        foreach ($this->revisions()->where("note", $note->getId())->order("created DESC")->limit($keep) as $row) {
            $keepIds[] = (int) $row->id;
        }

        if (sizeof($keepIds) === 0) {
            return;
        }

        $this->revisions()->where("note", $note->getId())->where("id NOT", $keepIds)->delete();
    }
}
