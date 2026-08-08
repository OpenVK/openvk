<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Stats extends VKAPIRequestHandler
{
    public function trackEvents(): string
    {
        return "sekos"; // stub
    }

    public function trackInstalledApps(): string
    {
        return ""; // stub
    }
}
