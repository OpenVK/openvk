<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class SupportAgent extends RowModel
{
    protected $tableName = "support_names";

    function getAgentId(): int
    {
        return $this->getRecord()->agent;
    }

    function getName(): ?string
    {
        return $this->getRecord()->name;
    }

    function getCanonicalName(): string
    {
        return $this->getName();
    }

    function getAvatarURL(): ?string
    {
        return $this->getRecord()->icon;
    }

    function isShowNumber(): int
    {
        return $this->getRecord()->numerate;
    }

    function getRealName(): string
    {
        return (new Users)->get($this->getAgentId())->getCanonicalName();
    }
}