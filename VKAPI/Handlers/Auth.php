<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\{Users as UsersRepo, Clubs as ClubsRepo, Posts as PostsRepo};

final class Auth extends VKAPIRequestHandler
{
    public function validateAccount(): object
    {
        // dummy function, always return passwd
        return (object) [
            "flow_name" => "need_password",
            "sid" => "1",
        ];
    }
}
