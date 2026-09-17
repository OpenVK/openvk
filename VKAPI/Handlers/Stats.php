<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Stats extends VKAPIRequestHandler
{
    public function trackEvents(mixed $events = ""): int
    {
        return 1;
    }
}
