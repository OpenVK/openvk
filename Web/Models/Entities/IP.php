<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;

class IP extends RowModel
{
    protected $tableName = "ip";

    public const RL_RESET     = 0;
    public const RL_CANEXEC   = 1;
    public const RL_VIOLATION = 2;
    public const RL_BANNED    = 3;

    public function getIp(): string
    {
        return inet_ntop($this->getRecord()->ip);
    }

    public function getDiscoveryDate(): DateTime
    {
        return new DateTime($this->getRecord()->first_seen);
    }

    public function isBanned(): bool
    {
        return (bool) $this->getRecord()->banned;
    }

    public function ban(): void
    {
        $this->stateChanges("banned", true);
        $this->save();
    }

    public function pardon(): void
    {
        $this->stateChanges("banned", false);
        $this->save();
    }

    public function clear(): void
    {
        $this->stateChanges("rate_limit_counter_start", 0);
        $this->stateChanges("rate_limit_counter", 0);
        $this->stateChanges("rate_limit_violation_counter_start", 0);
        $this->stateChanges("rate_limit_violation_counter", 0);
        $this->save();
    }

    public function rateLimit(int $actionComplexity = 1): int
    {
        $counterSessionStart  = $this->getRecord()->rate_limit_counter_start;
        $vCounterSessionStart = $this->getRecord()->rate_limit_violation_counter_start;

        $aCounter = $this->getRecord()->rate_limit_counter;
        $vCounter = $this->getRecord()->rate_limit_violation_counter;

        $config = (object) OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"];

        try {
            if ((time() - $config->time) > $counterSessionStart) {
                $counterSessionStart = time();
                $aCounter = $actionComplexity;

                return static::RL_RESET;
            }

            if (($aCounter + $actionComplexity) <= $config->actions) {
                $aCounter += $actionComplexity;

                return static::RL_CANEXEC;
            }

            if ((time() - $config->maxViolationsAge) > $vCounterSessionStart) {
                $vCounterSessionStart = time();
                $vCounter = 1;

                return static::RL_VIOLATION;
            }

            $vCounter += 1;
            if ($vCounter >= $config->maxViolations) {
                $this->stateChanges("banned", true);

                return static::RL_BANNED;
            }

            return static::RL_VIOLATION;
        } finally {
            $this->stateChanges("rate_limit_counter_start", $counterSessionStart);
            $this->stateChanges("rate_limit_counter", $aCounter);
            $this->stateChanges("rate_limit_violation_counter_start", $vCounterSessionStart);
            $this->stateChanges("rate_limit_violation_counter", $vCounter);
            $this->save(false);
        }
    }

    public function setIp(string $ip): void
    {
        $ip = inet_pton($ip);
        if (!$ip) {
            throw new \UnexpectedValueException("Malformed IP address");
        }

        $this->stateChanges("ip", $ip);
    }

    public function save(?bool $log = false): void
    {
        if (is_null($this->getRecord())) {
            $this->stateChanges("first_seen", time());
        }

        parent::save($log);
    }
}
