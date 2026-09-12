<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;

final class Status extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, int $group_id = 0)
    {
        if ($user_id == 0 && $group_id == 0) {
            $this->requireUser();
            $user_id = $this->getUser()->getId();
        }

        if ($user_id < 0 || $group_id > 0) {
            $clubId = $group_id > 0 ? $group_id : abs($user_id);
            $club   = (new ClubsRepo())->get($clubId);

            if (!$club || ($this->getUser() && !$club->canBeViewedBy($this->getUser()))) {
                $this->fail(15, "Access denied");
            }

            return (object) [
                "text" => $club->getDescription() ?? "",
            ];
        } else {
            $user = (new UsersRepo())->get($user_id);

            if (!$user || $user->isDeleted() || ($this->getUser() && !$user->canBeViewedBy($this->getUser()))) {
                $this->fail(15, "Invalid user");
            }

            $audioStatus = $user->getCurrentAudioStatus();
            $res = [
                "text" => $user->getStatus() ?? "",
            ];

            if ($audioStatus) {
                $res["audio"] = $audioStatus->toVkApiStruct();
            }

            return (object) $res;
        }
    }

    public function set(string $text, int $group_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($group_id > 0) {
            $club = (new ClubsRepo())->get($group_id);
            if (!$club || !$club->canBeModifiedBy($this->getUser())) {
                $this->fail(15, "Access denied");
            }

            $club->setDescription($text);
            $club->save();

            return 1;
        } else {
            $this->getUser()->setStatus($text);
            $this->getUser()->save();

            return 1;
        }
    }
}

