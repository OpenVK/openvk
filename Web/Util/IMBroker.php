<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Predis\Client as RedisClient;
use Exception;

class IMBroker 
{
    private static ?self $instance = null;
    private RedisClient $redis;
    private string $prefix = 'im:session:api:';
    private bool $enabled;
    private string $serverUrl;

    private function __construct()
    {
        $conf = OPENVK_ROOT_CONF["openvk"]["credentials"]["im"] ?? [];
        $redisConf = OPENVK_ROOT_CONF["openvk"]["credentials"]["redis"] ?? ['addr' => '127.0.0.1', 'port' => 6379];

        $this->enabled = (bool) ($conf['enable'] ?? false);
        $this->serverUrl = $conf['server_url'] ?? "http://127.0.0.1:8080";

        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => $redisConf["addr"],
            'port'   => (int) $redisConf["port"],
            'password' => $redisConf["password"] ?? null,
        ]);
    }

    public static function i(): self
    {
        return self::$instance ??= new self();
    }

    public function isConnected(): bool
    {
        try {
            $this->redis->ping();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getLongPollBaseUrl(): string
    {
        $configUrl = OPENVK_ROOT_CONF["openvk"]["credentials"]["im"]["lp_server_addr"] ?? null;
        
        if ($configUrl) {
            return (ovk_is_ssl() ? "https://" : "http://") . $configUrl . "/nim";
        }

        return (ovk_is_ssl() ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/nim";
    }

    public function ping($uid): bool 
    {
        $resp = $this->invokeMethod($uid, "im.GetMe");
        if (!$resp) return false;
        return true;
    }

    public function pingLP(): bool
    {
        try {
            $ctx = stream_context_create([
                "http" => [
                    "timeout" => 2,
                    "ignore_errors" => true
                ]
            ]);
            
            $ping = @file_get_contents($this->getLongPollBaseUrl() . "?health=1", false, $ctx);
            
            if ($ping === "OK") {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function invokeMethod(int $userId, string $method, array $queryParams = [])
    {
        if (!$this->enabled) {
            return false;
        }
        return $this->invokeWithKey($userId, function(string $key, string $serverUrl) use ($method, $queryParams) {
            $params = array_merge(['key' => $key], $queryParams);
            
            $baseUrl = rtrim($serverUrl, '/');

            $url = $baseUrl . '/method/' . ltrim($method, '/') . '?' . http_build_query($params);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ]);

            return @file_get_contents($url, false, $context);
        });
    }

    public function invokeWithKey(int $userId, callable $callback)
    {
        $key = $this->getOneActionKey($userId);
        
        try {
            return $callback($key, $this->serverUrl);
        } finally {
            $this->redis->del($this->prefix . $key);
        }
    }

    public function getOneActionKey(int $userId): string
    {
        if (!$this->enabled) {
            throw new Exception("IM Broker is disabled in configuration.");
        }

        $token = bin2hex(random_bytes(16)); 
        $redisKey = $this->prefix . $token;

        $this->redis->set($redisKey, $userId, 'EX', 60);

        return $token;
    }

}