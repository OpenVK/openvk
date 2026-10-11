<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Channels extends VKAPIRequestHandler
{
    public function getReactionsMapping(): object
    {
        $this->requireUser();

        return (object) ["mapping" => []];
    }
}
