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

    public function __construct()
    {
        $this->config = OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"];
    }

    /* 
    Checks count of actions for last x seconds.

    Uses OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["eventsLimit"]

    This check should be peformed only after checking other conditions cuz by default it increments counter

    Returns:

    true — limit has exceed and the action must be restricted

    false — the action can be performed
    */
    public function tryToLimit(?User $user, string $event_type, bool $is_update = true): bool
    {
        bdump("TRY TO LIMIT IS CALLLLED");
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
        $eventsStats = $user->getEventCounters($eventsList);

        $counters = $eventsStats["counters"];
        $refresh_time = $eventsStats["refresh_time"];
        $is_restrict_over = $refresh_time < (time() - $restrictionTime);
        bdump($refresh_time);
        bdump("time: " . time());
        $event_counter = $counters[$event_type];

        if ($refresh_time && $is_restrict_over) {
            bdump("RESETTING EVENT COUTNERS");
            $user->resetEvents($eventsList);

            return false;
        }

        $is_limit_exceed = $event_counter > $limitForThatEvent;

        bdump($is_limit_exceed);
        if (!$is_limit_exceed && $is_update) {
            $this->incrementEvent($counters, $event_type, $user);
        }

        return $is_limit_exceed;
    }

    /*
    Updates counter for user
    */
    public function incrementEvent(array $old_values, string $event_type, User $initiator): bool
    {
        bdump("INCREMENT IS CALLED");
        $isEnabled = $this->config['enable'];
        $eventsList = $this->config['list'];

        if (!$isEnabled) {
            return false;
        }
        bdump($old_values);

        $old_values[$event_type] += 1;

        bdump($old_values);
        $initiator->stateEvents($old_values);
        $initiator->save();

        return true;
    }
}
