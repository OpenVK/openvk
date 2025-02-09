<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;

interface Handler
{
    public function __construct(?User $user);
}
