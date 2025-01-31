<?php

declare(strict_types=1);

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
    use Traits\TSubscribable;
    protected $tableName = "group_coadmins";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getUserId(): int
    {
        return $this->getRecord()->user;
    }

    public function getUser(): ?User
    {
        return (new Users())->get($this->getRecord()->user);
    }

    public function getClubId(): int
    {
        return $this->getRecord()->club;
    }

    public function getClub(): ?Club
    {
        return (new Clubs())->get($this->getRecord()->club);
    }

    public function getComment(): string
    {
        return is_null($this->getRecord()->comment) ? "" : $this->getRecord()->comment;
    }

    public function isHidden(): bool
    {
        return (bool) $this->getRecord()->hidden;
    }

    public function isClubPinned(): bool
    {
        return (bool) $this->getRecord()->club_pinned;
    }
}
