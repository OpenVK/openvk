<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Calls extends VKAPIRequestHandler
{
    public function getHistory(int $count = 20, int $start_message_id = 0, string $fields = "", int $extended = 0, string $next_page_pagination_marker = ""): object
    {
        $this->requireUser();

        return (object) [
            "items"    => [],
            "profiles" => [],
            "groups"   => [],
            "contacts" => [],
            "has_more" => false,
        ];
    }
}
