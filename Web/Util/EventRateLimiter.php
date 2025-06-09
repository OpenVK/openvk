<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;
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

    private $config;
    private $availableFields;

    public function __construct()
    {
        $this->config = OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"];
        $this->availableFields = array_keys($this->config['list']);
    }

    public function tryToLimit(?User $user): bool
    {
        if (!$this->config['enable']) {
            return false;
        }

        if ($user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function writeEvent(string $event_name, User $initiator, ?RowModel $reciever = null): bool
    {
        if (!$this->config['enable']) {
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
            $data['receiverId'] = $reciever->getRealId();
        }

        $newEvent = new UserEvent($data);
        $newEvent->write($e);

        return true;
    }
}
