<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Repositories\{Users, Clubs};
use openvk\Web\Models\Entities\{User, Club};
use openvk\Web\Models\RowModel;

class Shortcodes
{
    static public function resolve(string $screen_name): ?RowModel
    {
        if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$screen_name")[0]->presenter !== "UnknownTextRouteStrategy") {
            if (substr($screen_name, 0, strlen("id")) === "id") {
                return get_entity_by_id((int) substr($screen_name, strlen("id")));
            } elseif (substr($screen_name, 0, strlen("club")) === "club") {
                return get_entity_by_id(((int) substr($screen_name, strlen("club"))) * -1);
            } else {
                return null;
            }
        } else {
            $user = (new Users())->getByShortURL($screen_name);
            if ($user) {
                return $user;
            }

            $club = (new Clubs())->getByShortURL($screen_name);
            if ($club) {
                return $club;
            }

            return null;
        }
    }
}
