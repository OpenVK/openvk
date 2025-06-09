<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use openvk\Web\Models\Entities\User;
use Chandler\Patterns\TSimpleSingleton;

class UserEvent
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function write($edb): bool
    {
        $edb->getConnection()->query("INSERT INTO `user-events` VALUES (?, ?, ?, ?, ?)", ...array_values($this->data));

        return true;
    }
}

class EventRateLimiter
{
    use TSimpleSingleton;

    public function writeEvent(string $event_name, User $initiator, ?User $reciever = null): bool
    {
        $eventsConfig = OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"];
        if (!$eventsConfig['enable']) {
            return false;
        }

        if (!($e = eventdb())) {
            return false;
        }

        $data = [
            'initiatorId' => $initiator->getId(),
            'initiatorIp' => null,
            'receiverId' => null,
            'eventType' => $event_name,
            'eventTime' => time()
        ];

        if ($reciever) {
            $data['receiverId'] = $reciever->getId();
        }

        $newEvent = new UserEvent($data);
        $newEvent->write($e);

        return true;
    }
}
