<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Search extends VKAPIRequestHandler
{
    public function getHints()
    {
        $this->requireUser();

        return [];
    }
}
