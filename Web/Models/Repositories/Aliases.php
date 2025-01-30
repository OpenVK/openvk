<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Alias;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Entities\{Club, User};
use openvk\Web\Models\Repositories\{Clubs, Users};

class Aliases
{
    private $context;
    private $aliases;

    public function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->aliases = $this->context->table("aliases");
    }

    private function toAlias(?ActiveRow $ar): ?Alias
    {
        return is_null($ar) ? null : new Alias($ar);
    }

    public function get(int $id): ?Alias
    {
        return $this->toAlias($this->aliases->get($id));
    }

    public function getByShortcode(string $shortcode): ?Alias
    {
        return $this->toAlias($this->aliases->where("shortcode", $shortcode)->fetch());
    }
}
