<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;

class Ticket extends RowModel
{
    use Traits\TRichText;
    protected $tableName = "tickets";

    private $overrideContentColumn = "text";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getStatus(): string
    {
        return tr("support_status_" . $this->getRecord()->type);
    }

    public function getType(): int
    {
        return $this->getRecord()->type;
    }

    public function getName(): string
    {
        return ovk_proc_strtr($this->getRecord()->name, 100);
    }

    public function getContext(): string
    {
        $text = $this->getRecord()->text;
        $text = $this->formatLinks($text);
        $text = $this->removeZalgo($text);
        $text = nl2br($text);
        return $text;
    }

    public function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }

    public function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    public function getUser(): user
    {
        return (new Users())->get($this->getRecord()->user_id);
    }

    public function getUserId(): int
    {
        return $this->getRecord()->user_id;
    }

    public function isAd(): bool /* Эх, костыли... */
    {
        return false;
    }
}
