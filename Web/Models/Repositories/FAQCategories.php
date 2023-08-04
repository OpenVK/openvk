<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\FAQCategory;

class FAQCategories
{
    private $context;
    private $categories;

    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->categories = $this->context->table("faq_categories");
    }

    function toFAQCategory(?ActiveRow $ar): ?FAQCategory
    {
        return is_null($ar) ? NULL : new FAQCategory($ar);
    }

    function get(int $id): ?FAQCategory
    {
        return $this->toFAQCategory($this->categories->get($id));
    }

    function getList(string $language, bool $includeForAgents = false): \Traversable
    {
        $filter = ["deleted" => 0, "language" => $language];
        if (!$includeForAgents) $filter["for_agents_only"] = 0;

        foreach ($this->categories->where($filter) as $category) {
            yield new FAQCategory($category);
        }
    }
}
