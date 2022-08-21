<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\{RowModel};
use openvk\Web\Models\Entities\{User};
use openvk\Web\Models\Repositories\{Users};

class BugtrackerProduct extends RowModel
{
    protected $tableName = "bt_products";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getCreator(): ?User
    {
        return (new Users)->get($this->getRecord()->creator_id);
    }

    function getName(): string
    {
        return $this->getRecord()->title;
    }

    function getCanonicalName(): string
    {
        return $this->getName();
    }

    function getDescription(): ?string
    {
        return $this->getRecord()->description;
    }

    function isClosed(): ?bool
    {
        return (bool) $this->getRecord()->closed;
    }

    function getCreationTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
}