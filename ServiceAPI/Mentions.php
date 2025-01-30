<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Users, Clubs};

class Mentions implements Handler
{
    protected $user;

    public function __construct(?User $user)
    {
        $this->user = $user;
    }

    public function resolve(int $id, callable $resolve, callable $reject): void
    {
        if ($id > 0) {
            $user = (new Users())->get($id);
            if (!$user) {
                $reject("Not found");
                return;
            }

            $resolve([
                "url"    => $user->getURL(),
                "name"   => $user->getFullName(),
                "ava"    => $user->getAvatarURL("miniscule"),
                "about"  => $user->getStatus() ?? "",
                "online" => ($user->isFemale() ? tr("was_online_f") : tr("was_online_m")) . " " . $user->getOnline(),
                "verif"  => $user->isVerified(),
            ]);
            return;
        }

        $club = (new Clubs())->get(abs($id));
        if (!$club) {
            $reject("Not found");
            return;
        }

        $resolve([
            "url"    => $club->getURL(),
            "name"   => $club->getName(),
            "ava"    => $club->getAvatarURL("miniscule"),
            "about"  => $club->getDescription() ?? "",
            "online" => tr("participants", $club->getFollowersCount()),
            "verif"  => $club->isVerified(),
        ]);
    }
}
