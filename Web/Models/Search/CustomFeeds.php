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
        $sub = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues_imports")
            ->select("catalogue")
            ->where("owner", $user->getRealId())
            ->order("ord DESC");

        $ids = [];

        foreach ($sub as $item) {
            $ids[] = $item->catalogue;
        }

        $select = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues")
            ->where("id IN (?)", $ids)
            ->where("deleted", 0)
            ->where("subtype", 1)
            ->order("id ASC");

        foreach ($select as $item) {
            yield new Catalogue($item);
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
