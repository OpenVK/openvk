<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Internal extends VKAPIRequestHandler
{
    public function getNotifications(
        ?string $device = null,
        ?string $os = null,
        mixed $app_version = null,
        ?string $locale = null
    ): array {
        return [];
        /*
        It would be a nice feature lol
        $host = $_SERVER["HTTP_HOST"];
        $serverUrl = ovk_scheme(true) . $host;

        return [
            [
                "title"   => "Welcome!",
                "message" => "MOTD here (host here)",
            ],
        ];*/
    }

    public function giveMeException(): void
    {
        throw new \RuntimeException("Test exception for server error interception");
    }
}
