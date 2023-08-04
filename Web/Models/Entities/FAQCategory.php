<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;

class FAQCategory extends RowModel
{
    protected $tableName = "faq_categories";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function canSeeByUsers(): bool
    {
        return (bool) !$this->getRecord()->for_agents_only;
    }

    function getIconBackgroundPosition(): int
    {
        return 28 * $this->getRecord()->icon;
    }

    function getArticles(?int $limit = NULL, $isAgent): \Traversable
    {
        $filter = ["category" => $this->getId(), "deleted" => 0];
        if (!$isAgent) $filter["users_can_see"] = 1;

        $articles = DatabaseConnection::i()->getContext()->table("faq_articles")->where($filter)->limit($limit);
        foreach ($articles as $article) {
            yield new FAQArticle($article);
        }
    }

    function getIcon(): int
    {
        return $this->getRecord()->icon;
    }

    function getLanguage(): string
    {
        return $this->getRecord()->language;
    }
}
