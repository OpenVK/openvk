<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Repositories\{Users, Clubs, Posts};
use openvk\Web\Models\Entities\User;

class Feed
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getGlobal() {}
    public function getLocal() {}
}
