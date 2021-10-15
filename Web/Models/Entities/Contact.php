<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Contacts;
use openvk\Web\Models\Repositories\{Users, Clubs};

class Contact extends RowModel 
{
    protected $tableName = "group_contacts";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getUser(): ?User
    {
        return (new Users)->get($this->getRecord()->user);
    }

    function getGroup(): ?Club
    {
        return (new Clubs)->get($this->getId());
    }

    function getDescription(): string
    {
        return ovk_proc_strtr($this->getRecord()->content, 32);
    }

    function getEmail(): string
    {
        return $this->getRecord()->email;
    }
}