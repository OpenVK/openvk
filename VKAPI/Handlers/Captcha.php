<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Captcha extends VKAPIRequestHandler
{
    public function force(): array {
        return [];
    }
}
