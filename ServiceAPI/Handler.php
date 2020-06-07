<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\User;

interface Handler
{
    function __construct(?User $user);
}