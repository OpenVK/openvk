<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{Gift, GiftCategory};
use Nette\Database\Table\ActiveRow;

class Gifts
{
    private $context;
    private $gifts;
    private $cats;

    /* aggressive sql caching */
    private static $cache = [];
    private static $cache_category = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->gifts   = $this->context->table("gifts");
        $this->cats    = $this->context->table("gift_categories");
    }

    private function toGift(?ActiveRow $ar): ?Gift
    {
        return is_null($ar) ? null : new Gift($ar);
    }

    private function toGiftCategory(?ActiveRow $ar): ?GiftCategory
    {
        return is_null($ar) ? null : new GiftCategory($ar);
    }

    public function get(int $id): ?Gift
    {
        return self::$cache[$id] ??= $this->toGift($this->gifts->get($id));
    }

    public function getCat(int $id): ?GiftCategory
    {
        return self::$cache_category[$id] ??= $this->toGiftCategory($this->cats->get($id));
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
        return $cats->count('*');
    }
}
