<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\SupportTemplatesDirs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class SupportTemplate extends RowModel
{
    protected $tableName = "support_templates";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getOwner(): ?User
    {
        return (new Users)->get($this->getRecord()->owner);
    }

    function getDir(): ?SupportTemplateDir
    {
        return (new SupportTemplatesDirs)->get($this->getRecord()->dir);
    }

    function isPublic(): bool
    {
        return $this->getDir()->isPublic();
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function getText(): string
    {
        return $this->getRecord()->text;
    }
}
