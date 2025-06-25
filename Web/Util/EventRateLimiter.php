<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;
use Chandler\Patterns\TSimpleSingleton;

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
    Checks count of actions for last hours

    Uses config path OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"]

    Returns:

    true — limit has exceed and the action must be restricted

    false — the action can be performed
    */
    public function tryToLimit(?User $user, string $event_type, bool $distinct = true): bool
    {
        $isEnabled = $this->config['enable'];
        $isIgnoreForAdmins = $this->config['ignoreForAdmins'];
        $restrictionTime = $this->config['restrictionTime'];
        $eventsList = $this->config['list'];

        if (!$isEnabled) {
            return false;
        }

        if ($isIgnoreForAdmins && $user->isAdmin()) {
            return false;
        }

        $limitForThatEvent = $eventsList[$event_type];
        $stat = $this->getEvent($event_type, $user);
        bdump($stat);

        $is_restrict_over = $stat["refresh_time"] < time() - $restrictionTime;

        if ($is_restrict_over) {
            $user->resetEvents($eventsList, $restrictionTime);

            return false;
        }

        $is = $stat["compared"] > $limitForThatEvent;

        if ($is === false) {
            $this->incrementEvent($event_type, $user);
        }

        return $is;
    }

    public function getEvent(string $event_type, User $by_user): array
    {
        $ev_data = $by_user->recieveEventsData($this->config['list']);
        $values  = $ev_data['counters'];
        $i = 0;

        $compared = [];
        bdump($values);

        foreach ($this->config['list'] as $name => $value) {
            bdump($value);
            $compared[$name] = $values[$i];
            $i += 1;
        }

        return [
            "compared" => $compared,
            "refresh_time" => $ev_data["refresh_time"]
        ];
    }

    /*
    Updates counter for user
    */
    public function incrementEvent(string $event_type, User $initiator): bool
    {
        $isEnabled = $this->config['enable'];
        $eventsList = OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"];

        if (!$isEnabled) {
            return false;
        }

        $ev_data = $initiator->recieveEventsData($eventsList);
        $values  = $ev_data['counters'];
        $i = 0;

        $compared = [];

        foreach ($eventsList as $name => $value) {
            $compared[$name] = $values[$i];
            $i += 1;
        }

        $compared[$event_type] += 1;

        bdump($compared);
        $initiator->stateEvents($compared);

        return true;
    }
}
