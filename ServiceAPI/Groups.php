<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Clubs;

class Groups implements Handler
{
    protected $user;
    protected $groups;

    public function __construct(?User $user)
    {
        $this->user  = $user;
        $this->groups = new Clubs();
    }

    public function getWriteableClubs(callable $resolve, callable $reject)
    {
        $clubs  = [];
        $wclubs = $this->groups->getWriteableClubs($this->user->getId());
        $count  = $this->groups->getWriteableClubsCount($this->user->getId());

        if (!$count) {
            $reject("You don't have any groups with write access");

            return;
        }

        foreach ($wclubs as $club) {
            $clubs[] = [
                "name"   => $club->getName(),
                "id"     => $club->getId(),
                "avatar" => $club->getAvatarUrl(), # если в овк когда-нибудь появится крутой список с аватарками, то можно использовать это поле
            ];
        }

        $resolve($clubs);
    }
}
