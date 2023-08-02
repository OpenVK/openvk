<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;

class CurrentUser
{
    private static $instance = null;
    private $user;

    public function __construct(?User $user = NULL)
    {
        if ($user)
            $this->user = $user;
    }

    public static function get($user)
    {
        if (self::$instance === null) self::$instance = new self($user);
        return self::$instance;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public static function i()
    {
        return self::$instance;
    }
}
