<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\SupportTemplates;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class SupportTemplateDir extends RowModel
{
    protected $tableName = "support_templates_dirs";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getOwner(): ?User
    {
        return (new Users)->get($this->getRecord()->owner);
    }

    function isPublic(): bool
    {
        return (bool) $this->getRecord()->is_public;
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function getText(): string
    {
        return $this->getRecord()->text;
    }

    function getTemplates(): \Traversable
    {
        return (new SupportTemplates)->getListByDirId($this->getId());
    }
}
