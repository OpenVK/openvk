<?php declare(strict_types=1);
namespace openvk\Web\Models\Exceptions;

final class InvalidUserNameException extends \UnexpectedValueException
{
    protected $message = "Invalid real name supplied";
}