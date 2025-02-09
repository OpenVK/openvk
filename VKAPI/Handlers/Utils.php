<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\{Users, Clubs};

final class Utils extends VKAPIRequestHandler
{
    public function getServerTime(): int
    {
        return time();
    }

    public function resolveScreenName(string $screen_name): object
    {
        if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$screen_name")[0]->presenter !== "UnknownTextRouteStrategy") {
            if (substr($screen_name, 0, strlen("id")) === "id") {
                return (object) [
                    "object_id" => (int) substr($screen_name, strlen("id")),
                    "type"      => "user",
                ];
            } elseif (substr($screen_name, 0, strlen("club")) === "club") {
                return (object) [
                    "object_id" => (int) substr($screen_name, strlen("club")),
                    "type"      => "group",
                ];
            } else {
                $this->fail(104, "Not found");
            }
        } else {
            $user = (new Users())->getByShortURL($screen_name);
            if ($user) {
                return (object) [
                    "object_id" => $user->getId(),
                    "type"      => "user",
                ];
            }

            $club = (new Clubs())->getByShortURL($screen_name);
            if ($club) {
                return (object) [
                    "object_id" => $club->getId(),
                    "type"      => "group",
                ];
            }

            $this->fail(104, "Not found");
        }
    }

    public function resolveGuid(string $guid): object
    {
        $user = (new Users())->getByChandlerUserId($guid);
        if (is_null($user)) {
            $this->fail(104, "Not found");
        }

        return $user->toVkApiStruct($this->getUser());
    }
}
