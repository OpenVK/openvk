<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

final class Utils extends VKAPIRequestHandler
{
    function getServerTime(): int
    {
        return time();
    }
}
