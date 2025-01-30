<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class SupportAgent extends RowModel
{
    protected $tableName = "support_names";

    public function getAgentId(): int
    {
        return $this->getRecord()->agent;
    }

    public function getName(): ?string
    {
        return $this->getRecord()->name;
    }

    public function getCanonicalName(): string
    {
        return $this->getName();
    }

    public function getAvatarURL(): ?string
    {
        return $this->getRecord()->icon;
    }

    public function isShowNumber(): int
    {
        return $this->getRecord()->numerate;
    }

    public function getRealName(): string
    {
        return (new Users())->get($this->getAgentId())->getCanonicalName();
    }
}
