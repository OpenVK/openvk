<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Club};
use openvk\Web\Models\Repositories\{Users, Clubs};

class Alias extends RowModel
{
    protected $tableName = "aliases";
    
    function getOwnerId(): int
    {
        return $this->getRecord()->owner_id;
    }

    function getType(): string
    {
        if ($this->getOwnerId() < 0)
            return "club";

        return "user";
    }

    function getUser(): ?User
    {
        return (new Users)->get($this->getOwnerId());
    }

    function getClub(): ?Club
    {
        return (new Clubs)->get($this->getOwnerId() * -1);
    }
}
