<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Note;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Notes
{
    private $context;
    private $notes;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->notes   = $this->context->table("notes");
    }

    private function toNote(?ActiveRow $ar): ?Note
    {
        return is_null($ar) ? null : new Note($ar);
    }

    public function get(int $id): ?Note
    {
        return $this->toNote($this->notes->get($id));
    }

    public function getUserNotes(User $user, int $page = 1, ?int $perPage = null, string $sort = "DESC"): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        foreach ($this->notes->where("owner", $user->getId())->where("deleted", 0)->order("created $sort")->page($page, $perPage) as $album) {
            yield new Note($album);
        }
    }

    public function getNoteById(int $owner, int $note): ?Note
    {
        $note = $this->notes->where(['owner' => $owner, 'virtual_id' => $note])->fetch();
        if (!is_null($note)) {
            return new Note($note);
        } else {
            return null;
        }
    }

    public function getUserNotesCount(User $user): int
    {
        return sizeof($this->notes->where("owner", $user->getId())->where("deleted", 0));
    }
}
