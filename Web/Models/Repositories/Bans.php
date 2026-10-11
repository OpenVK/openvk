<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\{ActiveRow, Selection};
use openvk\Web\Models\Entities\Ban;

class Bans
{
    private $context;
    private $bans;

    private static array $cache = [];

    public function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->bans = $this->context->table("bans");
    }

    public function toBan(?ActiveRow $ar): ?Ban
    {
        return is_null($ar) ? null : new Ban($ar);
    }

    public function get(int $id): ?Ban
    {
        if (!array_key_exists($id, self::$cache)) {
            self::$cache[$id] = $this->toBan($this->bans->get($id));
        }

        return self::$cache[$id];
    }

    public function getByUser(int $user_id): \Traversable
    {
        foreach ($this->bans->where("user", $user_id) as $ban) {
            yield new Ban($ban);
        }
    }
}
