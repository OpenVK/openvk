<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{Gift, GiftCategory};

class Gifts
{
    private $context;
    private $gifts;
    private $cats;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->gifts   = $this->context->table("gifts");
        $this->cats    = $this->context->table("gift_categories");
    }

    public function get(int $id): ?Gift
    {
        $gift = $this->gifts->get($id);
        if (!$gift) {
            return null;
        }

        return new Gift($gift);
    }

    public function getCat(int $id): ?GiftCategory
    {
        $cat = $this->cats->get($id);
        if (!$cat) {
            return null;
        }

        return new GiftCategory($cat);
    }

    public function getCategories(int $page, ?int $perPage = null, &$count = nullptr): \Traversable
    {
        $cats  = $this->cats->where("deleted", false);
        $count = $cats->count();
        $cats  = $cats->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        foreach ($cats as $cat) {
            yield new GiftCategory($cat);
        }
    }

    public function getCategoriesCount(): int
    {
        $cats  = $this->cats->where("deleted", false);
        return $cats->count();
    }
}
