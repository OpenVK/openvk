<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\Repositories\{Users};
use Nette\Database\Table\ActiveRow;

class Ban extends RowModel
{
    protected $tableName = "bans";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getReason(): ?string
    {
        return $this->getRecord()->reason;
    }

    public function getUser(): ?User
    {
        return (new Users())->get($this->getRecord()->user);
    }

    public function getInitiator(): ?User
    {
        return (new Users())->get($this->getRecord()->initiator);
    }

    public function getStartTime(): int
    {
        return $this->getRecord()->iat;
    }

    public function getEndTime(): int
    {
        return $this->getRecord()->exp;
    }

    public function getTime(): int
    {
        return $this->getRecord()->time;
    }

    public function isPermanent(): bool
    {
        return $this->getEndTime() === 0;
    }

    public function isRemovedManually(): bool
    {
        return (bool) $this->getRecord()->removed_manually;
    }

    public function isOver(): bool
    {
        return $this->isRemovedManually();
    }

    public function whoRemoved(): ?User
    {
        return (new Users())->get($this->getRecord()->removed_by);
    }
}
