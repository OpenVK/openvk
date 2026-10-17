<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Predis\Client as RedisClient;
use openvk\Web\Models\Entities\IP;
use openvk\Web\Models\Repositories\IPs;
use Chandler\Patterns\TSimpleSingleton;
use Throwable;

class RateLimiter
{
    use TSimpleSingleton;

    public const RL_RESET     = IP::RL_RESET;
    public const RL_CANEXEC   = IP::RL_CANEXEC;
    public const RL_VIOLATION = IP::RL_VIOLATION;
    public const RL_BANNED    = IP::RL_BANNED;

    private ?RedisClient $redis = null;
    private bool $redisAvailable = false;
    private array $config;

    public function __construct()
    {
        $this->config = OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"] ?? [
            "actions" => 5,
            "time" => 20,
            "maxViolations" => 50,
            "maxViolationsAge" => 120,
            "autoban" => true,
        ];

        $redisConf = OPENVK_ROOT_CONF["openvk"]["credentials"]["redis"] ?? null;
        $redisEnabled = !empty($redisConf) && !empty($redisConf["addr"]) && ($redisConf["enable"] ?? true);
        if ($redisEnabled && class_exists(RedisClient::class)) {
            try {
                $this->redis = new RedisClient([
                    'scheme'   => 'tcp',
                    'host'     => $redisConf["addr"],
                    'port'     => (int) ($redisConf["port"] ?? 6379),
                    'password' => !empty($redisConf["password"]) ? $redisConf["password"] : null,
                    'timeout'  => 0.5,
                ]);
                $this->redis->ping();
                $this->redisAvailable = true;
            } catch (Throwable $e) {
                $this->redis = null;
                $this->redisAvailable = false;
            }
        }
    }

    public function isRedisAvailable(): bool
    {
        return $this->redisAvailable && $this->redis !== null;
    }

    /**
     * General rate limiter with Redis backend and MySQL IP fallback.
     */
    public function rateLimit(
        string $scope,
        string $identifier,
        ?int $maxActions = null,
        ?int $timeWindow = null,
        int $actionComplexity = 1,
        ?string $ipFallback = null
    ): int {
        if (!$this->isRedisAvailable()) {
            return $this->fallbackIpRateLimit($ipFallback, $actionComplexity);
        }

        $maxActions       ??= (int) ($this->config["actions"] ?? 5);
        $timeWindow       ??= (int) ($this->config["time"] ?? 20);
        $maxViolations    = (int) ($this->config["maxViolations"] ?? 50);
        $maxViolationsAge = (int) ($this->config["maxViolationsAge"] ?? 120);

        try {
            $actionKey    = "rl:act:{$scope}:{$identifier}";
            $violationKey = "rl:viol:{$scope}:{$identifier}";
            $banKey       = "rl:ban:{$scope}:{$identifier}";

            if ($this->redis->exists($banKey)) {
                return self::RL_BANNED;
            }

            if ($actionComplexity <= 0) {
                $current = (int) ($this->redis->get($actionKey) ?: 0);
                return ($current <= $maxActions) ? self::RL_CANEXEC : self::RL_VIOLATION;
            }

            $currentActions = (int) $this->redis->incrby($actionKey, $actionComplexity);
            if ($currentActions === $actionComplexity) {
                $this->redis->expire($actionKey, $timeWindow);
                return self::RL_RESET;
            }

            if ($currentActions <= $maxActions) {
                return self::RL_CANEXEC;
            }

            // Exceeded limit -> record violation
            $violations = (int) $this->redis->incr($violationKey);
            if ($violations === 1) {
                $this->redis->expire($violationKey, $maxViolationsAge);
            }

            if ($violations >= $maxViolations) {
                $this->redis->setex($banKey, $maxViolationsAge * 2, '1');
                return self::RL_BANNED;
            }

            return self::RL_VIOLATION;
        } catch (Throwable $e) {
            $this->redisAvailable = false;
            return $this->fallbackIpRateLimit($ipFallback, $actionComplexity);
        }
    }

    public function getMethodConfig(string $method): ?array
    {
        if (empty($method)) {
            return null;
        }

        if (isset($this->config["methods"][$method]) && is_array($this->config["methods"][$method])) {
            return $this->config["methods"][$method];
        }

        if (isset($this->config[$method]) && is_array($this->config[$method])) {
            return $this->config[$method];
        }

        if (str_contains($method, ".")) {
            $parts = explode(".", $method);
            $curr = $this->config;
            foreach ($parts as $part) {
                if (is_array($curr) && isset($curr[$part])) {
                    $curr = $curr[$part];
                } else {
                    $curr = null;
                    break;
                }
            }
            if (is_array($curr)) {
                return $curr;
            }

            if (isset($this->config["methods"]) && is_array($this->config["methods"])) {
                $curr = $this->config["methods"];
                foreach ($parts as $part) {
                    if (is_array($curr) && isset($curr[$part])) {
                        $curr = $curr[$part];
                    } else {
                        $curr = null;
                        break;
                    }
                }
                if (is_array($curr)) {
                    return $curr;
                }
            }
        }

        return null;
    }

    /**
     * Write action rate limiter (used in willExecuteWriteAction).
     */
    public function limitWrite(
        string $ip,
        ?int $userId = null,
        int $complexity = 1,
        string $method = ""
    ): int {
        $methodConf = $this->getMethodConfig($method);

        if (!empty($methodConf["bypass"])) {
            return self::RL_CANEXEC;
        }

        $maxActions = isset($methodConf["actions"]) ? (int) $methodConf["actions"] : null;
        $timeWindow = isset($methodConf["time"]) ? (int) $methodConf["time"] : null;

        $scope = !empty($method) ? "write:" . str_replace(".", ":", $method) : "write";
        $identifier = ($userId && $userId > 0) ? "u_{$userId}" : "ip_" . md5($ip);

        return $this->rateLimit(
            $scope,
            $identifier,
            $maxActions,
            $timeWindow,
            $complexity,
            $ip
        );
    }

    private function fallbackIpRateLimit(?string $ip, int $complexity): int
    {
        $targetIp = $ip ?: (defined('CONNECTING_IP') ? CONNECTING_IP : '127.0.0.1');
        try {
            return (new IPs())->get($targetIp)->rateLimit($complexity);
        } catch (Throwable $e) {
            return self::RL_CANEXEC;
        }
    }
}
