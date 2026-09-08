<?php

declare(strict_types=1);

namespace openvk\VKAPI;

class ClientRegistry
{
    /** @var array<int, array>|null */
    private static ?array $clients = null;

    /**
     * Loads and caches clients from clients.xml.
     *
     * @return array<int, array>
     */
    public static function getAll(): array
    {
        if (self::$clients !== null) {
            return self::$clients;
        }

        self::$clients = [];

        $xmlPath = defined('OPENVK_ROOT')
            ? OPENVK_ROOT . '/data/clients.xml'
            : dirname(__DIR__) . '/data/clients.xml';

        if (!file_exists($xmlPath)) {
            return self::$clients;
        }

        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            return self::$clients;
        }

        foreach ($xml->Client as $client) {
            $id = isset($client['id']) && is_numeric((string) $client['id'])
                ? (int) $client['id']
                : null;
            $tag      = (string) ($client['tag'] ?? '');
            $name     = (string) ($client['name'] ?? '');
            $platform = (string) ($client['platform'] ?? 'api');
            $url      = isset($client['url']) && (string) $client['url'] !== '' ? (string) $client['url'] : null;
            $img      = isset($client['img']) && (string) $client['img'] !== '' ? (string) $client['img'] : null;

            self::$clients[] = [
                'id'       => $id,
                'tag'      => $tag,
                'name'     => $name,
                'platform' => $platform,
                'url'      => $url,
                'img'      => $img,
            ];
        }

        return self::$clients;
    }

    /**
     * Finds client by numeric App ID.
     */
    public static function getById(int $id): ?array
    {
        foreach (self::getAll() as $client) {
            if ($client['id'] === $id) {
                return $client;
            }
        }

        return null;
    }

    /**
     * Finds client by exact or case-insensitive tag.
     */
    public static function getByTag(string $tag): ?array
    {
        foreach (self::getAll() as $client) {
            if ($client['tag'] === $tag || strcasecmp($client['tag'], $tag) === 0) {
                return $client;
            }
        }

        return null;
    }

    /**
     * Resolves client info by ID, tag, name, or platform family.
     * Falls back to database Applications repository if not found in XML.
     */
    public static function resolve(mixed $key): ?array
    {
        if (empty($key)) {
            return null;
        }

        if (is_numeric($key) && (int) $key > 0) {
            $client = self::getById((int) $key);
            if ($client) {
                return $client;
            }
        }

        $strKey = (string) $key;

        $client = self::getByTag($strKey);
        if ($client) {
            return $client;
        }

        if (str_starts_with(strtolower($strKey), 'vk_')) {
            $client = self::getByTag(substr($strKey, 3));
            if ($client) {
                return $client;
            }
        } else {
            $client = self::getByTag('vk_' . $strKey);
            if ($client) {
                return $client;
            }
        }

        foreach (self::getAll() as $c) {
            if (strcasecmp($c['name'], $strKey) === 0) {
                return $c;
            }
        }

        $normKey = strtolower(trim($strKey));
        foreach (self::getAll() as $c) {
            $tagParts = preg_split('/[\s_-]+/', strtolower($c['tag']));
            if (!empty($tagParts) && $tagParts[0] === $normKey) {
                return $c;
            }
            $nameParts = preg_split('/[\s_-]+/', strtolower($c['name']));
            if (!empty($nameParts) && $nameParts[0] === $normKey) {
                return $c;
            }
        }

        $primaryId = self::getPlatformPrimaryId($strKey);
        if ($primaryId !== null) {
            $client = self::getById($primaryId);
            if ($client) {
                return $client;
            }
        }

        if (is_numeric($key) && (int) $key > 0) {
            try {
                $app = (new \openvk\Web\Models\Repositories\Applications())->get((int) $key);
                if ($app) {
                    return [
                        'id'       => $app->getId(),
                        'tag'      => $app->getName(),
                        'name'     => $app->getName(),
                        'platform' => 'api',
                        'url'      => null,
                        'img'      => null,
                    ];
                }
            } catch (\Throwable $e) {

            }
        }

        return null;
    }

    /**
     * Maps a client platform tag to the API platform family ('android', 'iphone', 'wphone', 'mobile', 'api').
     * Used by Post::getPlatform(true) and User::getOnlinePlatform(true).
     */
    public static function getPlatformForApi(?string $tag): ?string
    {
        if (is_null($tag) || $tag === '') {
            return null;
        }

        $client = self::resolve($tag);
        if ($client && !empty($client['platform'])) {
            return $client['platform'];
        }

        return 'api';
    }

    /**
     * Returns client details array (tag, name, url, img).
     * Used by Post::getPlatformDetails() and User::getOnlinePlatformDetails().
     */
    public static function getDetails(?string $tag): array
    {
        $client = !empty($tag) ? self::resolve($tag) : null;
        if ($client) {
            return [
                'tag'  => $client['tag'],
                'name' => $client['name'],
                'url'  => $client['url'],
                'img'  => $client['img'],
            ];
        }

        return [
            'tag'  => $tag,
            'name' => null,
            'url'  => null,
            'img'  => null,
        ];
    }

    /**
     * Maps platform family to the primary official App ID
     */
    public static function getPlatformPrimaryId(?string $platform): ?int
    {
        return match ($platform) {
            'vk_android', 'android'                 => 2274003,
            'vk_iphone', 'iphone', 'vk_ios', 'ios'  => 3140623,
            'vk_wphone', 'wphone', 'windows_phone'  => 5027722,
            'mobile', 'vk_mobile'                   => 10006,
            default                                 => null,
        };
    }

    /**
     * Returns an ordered list of candidate folder names inside VKAPI/Procedures/
     * for a given client platform and client ID. Folders are named by App ID.
     *
     * @return string[]
     */
    public static function getFolderCandidates(?string $platform, mixed $clientId = null): array
    {
        $rawCandidates = [];

        $clientFromId = !empty($clientId) ? self::resolve($clientId) : null;
        $clientFromPlatform = !empty($platform) ? self::resolve($platform) : null;

        if (!empty($clientId)) {
            $rawCandidates[] = (string) $clientId;
        }
        if ($clientFromId && !empty($clientFromId['id'])) {
            $rawCandidates[] = (string) $clientFromId['id'];
        }
        if ($clientFromPlatform && !empty($clientFromPlatform['id'])) {
            $rawCandidates[] = (string) $clientFromPlatform['id'];
        }

        $plat = $clientFromId['platform'] ?? $clientFromPlatform['platform'] ?? $platform;
        $primaryId = self::getPlatformPrimaryId($plat);
        if ($primaryId !== null) {
            $rawCandidates[] = (string) $primaryId;
        }

        if (!empty($plat)) {
            $rawCandidates[] = $plat;
        }

        $sanitized = [];
        foreach ($rawCandidates as $cand) {
            $cand = trim((string) $cand);

            $clean = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $cand);
            if (!empty($clean) && !in_array($clean, ['.', '..'], true) && !in_array($clean, $sanitized, true)) {
                $sanitized[] = $clean;
            }
        }

        return $sanitized;
    }
}
