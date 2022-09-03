<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Club};
use openvk\Web\Models\Repositories\{Users, Clubs};

class Alias extends RowModel
{
    protected $tableName = "aliases";
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getType(): string
    {
        if ($this->getId() < 0)
            return "club";

        return "user";
    }

    function getUser(): ?User
    {
        return (new Users)->get($this->getId());
    }

    function getClub(): ?Club
    {
        return (new Clubs)->get($this->getId() * -1);
    }
}
