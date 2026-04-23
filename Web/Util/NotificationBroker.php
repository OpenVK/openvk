<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Predis\Client as RedisClient;
use Exception;

class NotificationBroker
{
    private static ?self $instance = null;
    private RedisClient $redis;
    private string $streamPrefix = "ovk_notifs:";
    private int $maxlen;

    private function __construct()
    {
        $conf = OPENVK_ROOT_CONF["openvk"]["credentials"]["notificationsBroker"];
        $redisConf = $conf["redis"] ?? ['addr' => '127.0.0.1', 'port' => 6379]; // я люблю фоллбеки :з

        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => $redisConf["addr"],
            'port'   => (int) $redisConf["port"],
            'password' => $redisConf["password"] ?? null,
        ]);
        $this->streamPrefix = ($conf["stream"]["prefix"] ?? "ovk_notifs") . ":";
        $this->maxlen = (int) ($conf["stream"]["maxlen"] ?? 1000);
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

    public function push(int $userId, array $data): ?string
    {
        try {
            $streamKey = $this->streamPrefix . $userId;

            $arguments = [
                'XADD',
                $streamKey,
                'MAXLEN',
                '~',
                (string) $this->maxlen,
                '*',
                'payload',
                json_encode($data),
            ];

            $id = $this->redis->executeRaw($arguments);
            $this->redis->expire($streamKey, 60 * 10); // key will expire in 10 minutes

            return $id;
        } catch (Exception $e) {
            error_log("NotificationBroker push error: " . $e->getMessage());
            return null;
        }
    }

    public function getNew(int $userId, string $lastId = '0'): array
    {
        $streamKey = $this->streamPrefix . $userId;
        if (empty($lastId)) {
            $lastId = '0';
        }

        try {
            $response = $this->redis->executeRaw(['XREAD', 'STREAMS', $streamKey, $lastId]);

            if (empty($response) || !is_array($response)) {
                return [];
            }

            $events = [];

            foreach ($response as $streamData) {
                if (!isset($streamData[1]) || !is_array($streamData[1])) {
                    continue;
                }

                foreach ($streamData[1] as $message) {
                    if (!is_array($message) || !isset($message[1]) || !is_array($message[1])) {
                        continue;
                    }

                    $id = $message[0];
                    $fields = $message[1];
                    $data = [];

                    for ($i = 0; $i < count($fields); $i += 2) {
                        if (isset($fields[$i]) && isset($fields[$i + 1])) {
                            $data[$fields[$i]] = $fields[$i + 1];
                        }
                    }

                    if (isset($data['payload'])) {
                        $decoded = json_decode($data['payload'], true);
                        if ($decoded) {
                            $events[] = [
                                'id'   => (string) $id,
                                'data' => $decoded,
                            ];
                        }
                    }
                }
            }

            return $events;
        } catch (Exception $e) {
            error_log("NotificationBroker read error: " . $e->getMessage());
            return [];
        }
    }

    public function getLatestId(int $userId): string
    {
        $streamKey = $this->streamPrefix . $userId;
        $res = $this->redis->xrevrange($streamKey, '+', '-', 'COUNT', 1);

        return !empty($res) ? (string) key($res) : '$';
    }
}
