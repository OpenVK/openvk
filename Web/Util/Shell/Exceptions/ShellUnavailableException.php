<?php

declare(strict_types=1);

namespace openvk\Web\Util\Shell\Exceptions;

class ShellUnavailableException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Shell access is not permitted.", 23000);
    }
}
