<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\{Notifications as Notifs, Clubs, Users};
use openvk\Web\Util\NotificationBroker;

final class Notifications extends VKAPIRequestHandler
{
    public function get(
        int $count = 10,
        string $from = "",
        int $offset = 0,
        string $start_from = "",
        string $filters = "",
        int $start_time = 0,
        int $end_time = 0,
        int $archived = 0
    ) {
        $this->requireUser();

        $res = (object) [
            "items"       => [],
            "profiles"    => [],
            "groups"      => [],
            "last_viewed" => $this->getUser()->getNotificationOffset(),
        ];

        if ($count > 100) {
            $this->fail(125, "Count is too big");
        }

        if (!eventdb()) {
            $this->fail(1289, "EventDB is disabled on this instance");
        }

        $notifs = array_slice(iterator_to_array((new Notifs())->getNotificationsByUser($this->getUser(), $this->getUser()->getNotificationOffset(), (bool) $archived, 1, $offset + $count)), $offset);
        $tmpProfiles = [];
        foreach ($notifs as $notif) {
            $sxModel = $notif->getModel(1);

            if (!method_exists($sxModel, "getAvatarUrl")) {
                $sxModel = $notif->getModel(0);
            }


            $tmpProfiles[] = $sxModel instanceof Club ? $sxModel->getId() * -1 : $sxModel->getId();
            $res->items[] = $notif->toVkApiStruct();
        }

        foreach (array_unique($tmpProfiles) as $id) {
            if ($id > 0) {
                $sxModel = (new Users())->get($id);
                $result  = (object) [
                    "uid"        => $sxModel->getId(),
                    "first_name" => $sxModel->getFirstName(),
                    "last_name"  => $sxModel->getLastName(),
                    "photo"      => $sxModel->getAvatarUrl(),
                    "photo_medium_rec" => $sxModel->getAvatarUrl("tiny"),
                    "screen_name"      => $sxModel->getShortCode(),
                ];

                $res->profiles[] = $result;
            } else {
                $sxModel = (new Clubs())->get(abs($id));
                $result  = $sxModel->toVkApiStruct($this->getUser());

                $res->groups[] = $result;
            }
        }

        return $res;
    }

    public function markAsViewed()
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        try {
            $this->getUser()->updateNotificationOffset();
            $this->getUser()->save();
        } catch (\Throwable $e) {
            return 0;
        }

        return 1;
    }

    public function fetch(string $last_id = "0")
    {
        $this->requireUser();
        $userId = $this->getUser()->getId();
        
        $res = (object) [
            "items"    => [],
            "profiles" => [],
            "groups"   => [],
            "next_last_id" => $last_id,
        ];

        try {
            $broker = NotificationBroker::i();
            $events = $broker->getNew($userId, $last_id);

            if (empty($events)) {
                return $res;
            }

            $tmpProfiles = [];
            $tmpGroups   = [];

            foreach ($events as $event) {
                $currentId = $event['id'];
                $rawPayload = $event['data'];
                
                $notification = (new Notifs)->fromArray($rawPayload);
                
                if (!$notification) {
                    continue;
                }

                $res->items[] = $notification->toVkApiStruct();
                $res->new_lastId = $currentId;

                $sxModel = $notification->getModel(1);
                if (!method_exists($sxModel, "getAvatarUrl")) {
                    $sxModel = $notification->getModel(0);
                }

                if ($sxModel instanceof Club) {
                    $tmpGroups[] = $sxModel;
                } elseif ($sxModel instanceof Users) {
                    $tmpProfiles[] = $sxModel;
                }
            }

            foreach (array_unique($tmpProfiles, SORT_REGULAR) as $user) {
                $res->profiles[] = (object) [
                    "uid"              => $user->getId(),
                    "first_name"       => $user->getFirstName(),
                    "last_name"        => $user->getLastName(),
                    "photo"            => $user->getAvatarUrl(),
                    "photo_medium_rec" => $user->getAvatarUrl("tiny"),
                    "screen_name"      => $user->getShortCode(),
                ];
            }

            foreach (array_unique($tmpGroups, SORT_REGULAR) as $club) {
                $res->groups[] = $club->toVkApiStruct($this->getUser());
            }

            return $res;

        } catch (\Exception $e) {
            $this->fail(1981, "Internal error during event processing");
        }
    }
}
