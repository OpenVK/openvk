<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\FAQCategories;
use openvk\Web\Models\RowModel;

class FAQArticle extends RowModel
{
    protected $tableName = "faq_articles";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function getText(): string
    {
        return $this->getRecord()->text;
    }

    function canSeeByUnloggedUsers(): bool
    {
        return (bool) $this->getRecord()->unlogged_can_see;
    }

    function canSeeByUsers(): bool
    {
        return (bool) $this->getRecord()->users_can_see;
    }

    function getCategory(): ?FAQCategory
    {
        return (new FAQCategories)->get($this->getRecord()->category);
    }

    function getLanguage(): string
    {
        return $this->getRecord()->language;
    }
}
