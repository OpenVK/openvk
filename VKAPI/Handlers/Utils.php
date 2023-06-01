<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\Alias;
use openvk\Web\Models\Repositories\Aliases;

final class Utils extends VKAPIRequestHandler
{
    function getServerTime(): int
    {
        return time();
    }

    function resolveScreenName(string $screen_name): object
    {
        if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$screen_name")[0]->presenter !== "UnknownTextRouteStrategy") {
            if (substr($screen_name, 0, strlen("id")) === "id") {
                return (object) [
                    "object_id" => intval(substr($screen_name, strlen("id"))),
                    "type"      => "user"
                ];
            } else if (substr($screen_name, 0, strlen("club")) === "club") {
                return (object) [
                    "object_id" => intval(substr($screen_name, strlen("club"))),
                    "type"      => "group"
                ];
            }
        } else {
            $alias = (new Aliases)->getByShortCode($screen_name);
            if (!$alias) return (object)[];
            return (object) [
                "object_id" => $alias->getOwnerId(),
                "type"      => $alias->getType()
            ];
        }
    }
}
