<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Activity extends VKAPIRequestHandler
{
    public function online(): int
    {
        $this->requireUser();

        $this->getUser()->updOnline($this->getPlatform());

        return 1;
    }
}
