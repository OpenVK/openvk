<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\User;

class Clients implements Handler
{
    protected $user;

    function __construct(?User $user)
    {
        $this->user = $user;
    }

    function resolve(string $client_name, callable $resolve, callable $reject): void
    {
        
    }
}
