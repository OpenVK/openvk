<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Util\DateTime;

class GroupPageRevision extends RowModel
{
    protected $tableName = "group_page_revisions";

    public function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    public function getPageId(): int
    {
        return (int) $this->getRecord()->page;
    }

    public function getEditor(): ?User
    {
        return (new Users())->get((int) $this->getRecord()->editor);
    }

    public function getTitle(): string
    {
        return (string) $this->getRecord()->title;
    }

    public function getSource(): string
    {
        return (string) $this->getRecord()->source;
    }

    public function getCreationTime(): DateTime
    {
        return new DateTime((int) $this->getRecord()->created);
    }
}
