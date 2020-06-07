<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Database\Table\Selection;

class TicketComment extends RowModel
{
    
    protected $tableName = "tickets_comments";

    function getId(): int
    {
        return $this->getRecord()->id;
    }
    function getUType(): int
    {
        return $this->getRecord()->user_type;
    }
    
    function getUser(): User 
    { 
        return (new Users)->get($this->getRecord()->user_id);
    } 
    
    function getContext(): string
    {
        return $this->getRecord()->text;
    }
    
    function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
    
}
