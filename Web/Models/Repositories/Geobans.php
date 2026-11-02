<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\Relationships\Geoban;

class Geobans
{
    private $context;
    private $geoblocks;

    private static array $cache = [];

    public function __construct()
    {
        $this->context   = DB::i()->getContext();
        $this->geoblocks = $this->context->table("geoblocks");
    }

    public function toGeoban(?ActiveRow $ar): ?Geoban
    {
        return is_null($ar) ? null : new Geoban($ar);
    }

    public function get(int $id): ?Geoban
    {
        if (!array_key_exists($id, self::$cache)) {
            self::$cache[$id] = $this->toGeoban($this->geoblocks->get($id));
        }

        return self::$cache[$id];
    }

    public function getList(int $page = 1): \Traversable
    {
        foreach ($this->geoblocks->order("id DESC")->page($page, OPENVK_DEFAULT_PER_PAGE) as $block) {
            yield new Geoban($block);
        }
    }
}
