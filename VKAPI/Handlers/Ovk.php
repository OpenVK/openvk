<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\{Users as UsersRepo, Clubs as ClubsRepo, Posts as PostsRepo};

final class Ovk extends VKAPIRequestHandler
{
    public function version(): string
    {
        return OPENVK_VERSION;
    }

    public function test(): object
    {
        return (object) [
            "authorized" => $this->userAuthorized(),
            "auth_with"  => $_GET["auth_mechanism"] ?? "access_token",
            "version"    => VKAPI_DECL_VER,
        ];
    }

    public function chickenWings(): string
    {
        return "крылышки";
    }

    public function aboutInstance(string $fields = "statistics,administrators,popular_groups,links", string $admin_fields = "", string $group_fields = ""): object
    {
        $fields = explode(',', $fields);
        $response = (object) [];

        if (in_array("statistics", $fields)) {
            $usersStats           = (new UsersRepo())->getStatistics();
            $clubsCount           = (new ClubsRepo())->getCount();
            $postsCount           = (new PostsRepo())->getCount();
            $response->statistics = (object) [
                "users_count"        => $usersStats->all,
                "online_users_count" => $usersStats->online,
                "active_users_count" => $usersStats->active,
                "groups_count"       => $clubsCount,
                "wall_posts_count"   => $postsCount,
            ];
        }

        if (in_array("administrators", $fields)) {
            $admins         = iterator_to_array((new UsersRepo())->getInstanceAdmins());
            $adminsResponse = (new Users($this->getUser()))->get(implode(',', array_map(function ($admin) {
                return $admin->getId();
            }, $admins)), $admin_fields, 0, sizeof($admins));
            $response->administrators = (object) [
                "count" => sizeof($admins),
                "items" => $adminsResponse,
            ];
        }

        if (in_array("popular_groups", $fields)) {
            $popularClubs  = iterator_to_array((new ClubsRepo())->getPopularClubs());
            $clubsResponse = (new Groups($this->getUser()))->getById(implode(',', array_map(function ($entry) {
                return $entry->club->getId();
            }, $popularClubs)), "", "members_count, " . $group_fields);

            $response->popular_groups = (object) [
                "count" => sizeof($popularClubs),
                "items" => $clubsResponse,
            ];
        }

        if (in_array("links", $fields)) {
            $response->links = (object) [
                "count" => sizeof(OPENVK_ROOT_CONF['openvk']['preferences']['about']['links']),
                "items" => is_null(OPENVK_ROOT_CONF['openvk']['preferences']['about']['links']) ? [] : OPENVK_ROOT_CONF['openvk']['preferences']['about']['links'],
            ];
        }

        return $response;
    }
}
