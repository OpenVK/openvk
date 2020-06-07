<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{Photo, Message, Correspondence};
use openvk\Web\Models\Repositories\{Users, Clubs, Albums, Notifications, Managers};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class Manager extends RowModel
{
    protected $tableName = "group_coadmins";
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }
        
    function getUserId(): string
    {
        return $this->getRecord()->user;
    }

    function getUser(): ?User
    {
        return (new Users)->get($this->getRecord()->user);
    }

    function getClubId(): string
    {
        return $this->getRecord()->club;
    }

    function getClub(): ?Club
    {
        return (new Clubs)->get($this->getRecord()->club);
    }

    function getComment(): string
    {
        return is_null($this->getRecord()->comment) ? "" : $this->getRecord()->comment;
    }
        
    use Traits\TSubscribable;
}
