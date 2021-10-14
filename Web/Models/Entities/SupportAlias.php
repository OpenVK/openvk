<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;

class SupportAlias extends RowModel
{
    protected $tableName = "support_names";
    
    function getUser(): User
    {
        return (new Users)->get($this->getRecord()->agent);
    }
    
    function getName(): string
    {
        return $this->getRecord()->name;
    }
    
    function getIcon(): ?string
    {
        return $this->getRecord()->icon;
    }
    
    function shouldAppendNumber(): bool
    {
        return (bool) $this->getRecord()->numerate;
    }
    
    function setAgent(User $agent): void
    {
        $this->stateChanges("agent", $agent->getId());
    }
    
    function setNumeration(bool $numerate): void
    {
        $this->stateChanges("numerate", $numerate);
    }
}
