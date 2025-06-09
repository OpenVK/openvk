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

    /* 
    Checks count of actions for last x seconds, returns true if limit has exceed

    x is OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"]["restrictionTime"]
    */
    public function tryToLimit(?User $user, string $event_type, bool $distinct = true): bool
    {
        if (!$this->config['enable']) {
            return false;
        }

        if (!($e = eventdb())) {
            return false;
        }

        if ($this->config['ignoreForAdmins'] && $user->isAdmin()) {
            return false;
        }

        $limitForThisEvent = $this->config['list'][$event_type];
        $compareTime = time() - $this->config['restrictionTime'];

        $query = "SELECT COUNT(".($distinct ? "DISTINCT(`receiverId`)" : "*").") as `cnt` FROM `user-events` WHERE `initiatorId` = ? AND `eventType` = ? AND `eventTime` > ?";

        $result = $e->getConnection()->query($query, ...[$user->getId(), $event_type, $compareTime]);
        $count = $result->fetch()->cnt;
        #bdump($count); exit();

        return $count > $limitForThisEvent;
    }

    /*
    Writes new event to `openvk-eventdb`.`user-events`
    */
    public function writeEvent(string $event_type, User $initiator, ?RowModel $reciever = null): bool
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
            'eventType' => $event_type,
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
