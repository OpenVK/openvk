<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Predis\Client as RedisClient;
use Throwable;

/**
 * Tiny Redis-backed cache for expensive read-only computations.
 * Uses the same Redis endpoint as the rest of OpenVK (openvk.credentials.redis),
 * falling back to Chandler's redisUrl, and silently degrades to a plain call
 * when Redis is not configured or unreachable.
 */
class Cache
{
    private const PREFIX = "openvk:cache:";

    private static ?RedisClient $redis = null;
    private static bool $initialized = false;

    private static function redis(): ?RedisClient
    {
        if (self::$initialized) {
            return self::$redis;
        }

        self::$initialized = true;

        if (!class_exists(RedisClient::class)) {
            return null;
        }

        try {
            $redisConf    = OPENVK_ROOT_CONF["openvk"]["credentials"]["redis"] ?? null;
            $redisEnabled = !empty($redisConf) && !empty($redisConf["addr"]) && ($redisConf["enable"] ?? true);
            if ($redisEnabled) {
                $client = new RedisClient([
                    'scheme'   => 'tcp',
                    'host'     => $redisConf["addr"],
                    'port'     => (int) ($redisConf["port"] ?? 6379),
                    'password' => !empty($redisConf["password"]) ? $redisConf["password"] : null,
                    'timeout'  => 0.5,
                ]);
            } elseif (!empty(CHANDLER_ROOT_CONF["redisUrl"])) {
                // Chandler-compatible configuration (cron state, signals)
                $client = new RedisClient(CHANDLER_ROOT_CONF["redisUrl"], ['timeout' => 0.5]);
            } else {
                return null;
            }

            $client->ping();
            self::$redis = $client;
        } catch (Throwable $e) {
            self::$redis = null;
        }

        return self::$redis;
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $redis = self::redis();
        if (!$redis) {
            return $callback();
        }

        try {
            $cached = $redis->get(self::PREFIX . $key);
            if ($cached !== null) {
                $decoded = json_decode($cached, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        } catch (Throwable $e) {
            return $callback();
        }

        $value = $callback();

        try {
            $redis->setex(self::PREFIX . $key, $ttl, json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            // caching is best-effort
        }

        return $value;
    }
}
