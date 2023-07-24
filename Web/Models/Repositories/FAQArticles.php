<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\FAQArticle;

class FAQArticles
{
    private $context;
    private $articles;

    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->articles = $this->context->table("faq_articles");
    }

    function toFAQArticle(?ActiveRow $ar): ?FAQArticle
    {
        return is_null($ar) ? NULL : new FAQArticle($ar);
    }

    function get(int $id): ?FAQArticle
    {
        return $this->toFAQArticle($this->articles->get($id));
    }
}
