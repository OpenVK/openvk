<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Note;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Notes
{
    private $context;
    private $notes;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->notes   = $this->context->table("notes");
    }
    
    private function toNote(?ActiveRow $ar): ?Note
    {
        return is_null($ar) ? NULL : new Note($ar);
    }
    
    function get(int $id): ?Note
    {
        return $this->toNote($this->notes->get($id));
    }
    
    function getUserNotes(User $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        foreach($this->notes->where("owner", $user->getId())->where("deleted", 0)->page($page, $perPage) as $album)
            yield new Note($album);
    }
    
    function getUserNotesCount(User $user): int
    {
        return sizeof($this->notes->where("owner", $user->getId())->where("deleted", 0));
    }
}
