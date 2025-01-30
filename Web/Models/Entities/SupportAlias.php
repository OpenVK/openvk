<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;

class SupportAlias extends RowModel
{
    protected $tableName = "support_names";

    public function getUser(): User
    {
        return (new Users())->get($this->getRecord()->agent);
    }

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getIcon(): ?string
    {
        return $this->getRecord()->icon;
    }

    public function shouldAppendNumber(): bool
    {
        return (bool) $this->getRecord()->numerate;
    }

    public function setAgent(User $agent): void
    {
        $this->stateChanges("agent", $agent->getId());
    }

    public function setNumeration(bool $numerate): void
    {
        $this->stateChanges("numerate", $numerate);
    }
}
