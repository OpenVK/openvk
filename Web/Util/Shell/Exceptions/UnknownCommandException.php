<?php declare(strict_types=1);
namespace openvk\Web\Util\Shell\Exceptions;

class UnknownCommandException extends \Exception
{
    function __construct(string $command)
    {
        parent::__construct("Command $command can't be found on this system.", 23001);
    }
}
