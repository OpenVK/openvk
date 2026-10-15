<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Search\Catalogue;
use openvk\Web\Models\Entities\User;
use Chandler\Database\DatabaseConnection;

class CustomFeeds
{
    public static function get(?User $user)
    {
        $items = Catalogue::getImported($user, 1);

        foreach ($items as $item) {
            yield $item;
        }
    }

    public static function getById(int $list_id): ?Catalogue
    {
        $sub = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues")
            ->where("id", $list_id);

        if (!$sub) {
            return null;
        }

        return new Catalogue($sub->fetch());
    }

    public static function addNew(): Catalogue
    {
        $cat = new Catalogue();
        $cat->setSubtype(1);

        return $cat;
    }
}
