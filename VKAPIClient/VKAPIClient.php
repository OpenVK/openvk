<?php

declare(strict_types=1);

namespace openvk\VKAPIClient;

/**
 * HTTP-клиент для реального VK API.
 *
 * Данные: openvk.yml → vk.api_url, vk.access_token, vk.api_version
 * Использование:
 *   $api = VKAPIClient::i();
 *   $users = $api->call("users.get", ["user_ids" => 1]);
 */
class VKAPIClient
{
    private string $apiUrl;
    private string $accessToken;
    private string $apiVersion;
    private bool $verifySsl;
    private int $cacheTtl;
    private string $cacheDir;

    /** @var array<string, array{data: array, time: int}> */
    private static array $responseCache = [];

    private static ?self $instance = null;

    public function __construct(
        ?string $apiUrl = null,
        ?string $accessToken = null,
        ?string $apiVersion = null,
        ?bool $verifySsl = null,
        ?int $cacheTtl = null,
    ) {
        $vkConf = OPENVK_ROOT_CONF["openvk"]["vk"] ?? [];

        $this->apiUrl = rtrim(
            $apiUrl ?? ($vkConf["api_url"] ?? "https://api.vk.com/method"),
            "/",
        );
        $this->accessToken = $accessToken ?? ($vkConf["access_token"] ?? "");
        $this->apiVersion = $apiVersion ?? ($vkConf["api_version"] ?? "5.131");
        $this->verifySsl = $verifySsl ?? ($vkConf["verify_ssl"] ?? true);
        $this->cacheTtl = $cacheTtl ?? ($vkConf["cache_ttl"] ?? 300);

        // Директория для файлового кеша (между запросами)
        $this->cacheDir = OPENVK_ROOT . "/tmp/cache/vk_api";
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public static function i(): self
    {
        return self::$instance ?? (self::$instance = new self());
    }

    /**
     * Строит ключ кеша по методу и параметрам.
     */
    private function buildCacheKey(string $method, array $params): string
    {
        ksort($params);

        return md5($method . ":" . serialize($params));
    }

    /**
     * Выполняет запрос к VK API с кешированием.
     *
     * @param string $method     Название метода (например, "users.get")
     * @param array  $params     Параметры запроса
     * @param string $httpMethod GET|POST
     * @return array Ответ VK API (ключ "response" или выбросит исключение)
     */
    public function call(
        string $method,
        array $params = [],
        string $httpMethod = "GET",
    ): array {

        bdump($method);
        bdump([...$params]);

        $params["access_token"] = $this->accessToken;
        $params["v"] = $this->apiVersion;

        // Auto-add minimal fields if not specified (avoids extra API calls for avatars)
        if (!isset($params["fields"]) && in_array($method, ["users.get", "groups.getById", "groups.get", "friends.get", "friends.getOnline", "groups.search", "users.search", "groups.getMembers"])) {
            $params["fields"] = "photo_50,photo_100";
        }

        // Проверка кеша (сначала in-memory, потом файловый)
        if ($this->cacheTtl > 0) {
            $cacheKey = $this->buildCacheKey($method, $params);

            // In-memory cache (время жизни — один запрос)
            if (isset(self::$responseCache[$cacheKey])) {
                $elapsed = time() - self::$responseCache[$cacheKey]["time"];
                if ($elapsed < $this->cacheTtl) {
                    return self::$responseCache[$cacheKey]["data"];
                }

                unset(self::$responseCache[$cacheKey]);
            }

            // Файловый кеш (между запросами)
            $cacheFile = $this->cacheDir . "/" . $cacheKey . ".json";
            if (file_exists($cacheFile)) {
                $elapsed = time() - filemtime($cacheFile);
                if ($elapsed < $this->cacheTtl) {
                    $cached = json_decode(file_get_contents($cacheFile), true);
                    if ($cached !== null) {
                        self::$responseCache[$cacheKey] = ["data" => $cached, "time" => time()];
                        return $cached;
                    }
                }

                @unlink($cacheFile);
            }
        }

        $result = $this->doRequest($method, $params, $httpMethod);

        // Сохранение в кеш
        if ($this->cacheTtl > 0 && isset($cacheKey)) {
            self::$responseCache[$cacheKey] = [
                "data" => $result,
                "time" => time(),
            ];

            // Файловый кеш
            @file_put_contents($cacheFile, json_encode($result));
        }

        return $result;
    }

    /**
     * Реальный HTTP-запрос к VK API.
     */
    private function doRequest(string $method, array $params, string $httpMethod): array
    {
        $url = $this->apiUrl . "/" . $method;

        $ch = curl_init();

        if (strtoupper($httpMethod) === "POST") {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($params),
            ]);
        } else {
            $url .= "?" . http_build_query($params);
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPGET => true,
            ]);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => "chrome",
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
        ]);

        if ($this->verifySsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $caBundle = null;
            if (PHP_SHLIB_SUFFIX === "dll") {
                $possiblePaths = [
                    __DIR__ . "/cacert.pem",
                    PHP_BINARY . "/../extras/ssl/cacert.pem",
                    PHP_BINARY . "/../ssl/cacert.pem",
                    "C:\\php\\extras\\ssl\\cacert.pem",
                    "C:\\php\\ssl\\cacert.pem",
                    "C:\\tools\\php\\extras\\ssl\\cacert.pem",
                    "C:\\tools\\php\\ssl\\cacert.pem",
                ];

                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $caBundle = $path;
                        break;
                    }
                }
            }

            if ($caBundle) {
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            }
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $sslHint = "";
            if (str_contains($curlError, "SSL")) {
                $sslHint = " (SSL error. Add 'verify_ssl: false' to openvk.yml under 'vk:' section, or install a CA bundle)";
            }

            throw new VKAPIException("cURL error: " . $curlError . $sslHint);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new VKAPIException(
                "JSON parse error: " . json_last_error_msg(),
            );
        }

        if (isset($decoded["error"])) {
            $err = $decoded["error"];
            throw new VKAPIException(
                "VK API error [{$err["error_code"]}]: {$err["error_msg"]}",
                (int) $err["error_code"],
            );
        }

        return $decoded["response"] ?? [];
    }

    /**
     * Очищает кеш ответов.
     */
    public static function clearCache(?string $method = null): void
    {
        if ($method === null) {
            self::$responseCache = [];
        } else {
            foreach (self::$responseCache as $key => $cached) {
                if (str_starts_with($key, md5($method . ":"))) {
                    unset(self::$responseCache[$key]);
                }
            }
        }
    }

    /**
     * Обёртка для users.get
     *
     * @see https://dev.vk.com/method/users.get
     */
    public function usersGet(array|int $userIds, array $fields = []): array
    {
        return $this->call("users.get", [
            "user_ids" => implode(",", (array) $userIds),
            "fields" => implode(",", $fields),
        ]);
    }

    /**
     * Обёртка для groups.getById
     *
     * @see https://dev.vk.com/method/groups.getById
     */
    public function groupsGetById(
        array|int $groupIds,
        array $fields = [],
    ): array {
        return $this->call("groups.getById", [
            "group_ids" => implode(",", (array) $groupIds),
            "fields" => implode(",", $fields),
        ]);
    }

    /**
     * Обёртка для groups.get (список групп пользователя)
     *
     * @see https://dev.vk.com/method/groups.get
     */
    public function groupsGet(
        int $userId,
        array $fields = [],
        int $count = 50,
        int $offset = 0,
    ): array {
        return $this->call("groups.get", [
            "user_id" => $userId,
            "extended" => 1,
            "fields" => implode(",", $fields),
            "count" => $count,
            "offset" => $offset,
        ]);
    }

    /**
     * Обёртка для wall.get
     *
     * @see https://dev.vk.com/method/wall.get
     */
    public function wallGet(
        int|string $ownerId,
        int $count = 20,
        int $offset = 0,
        array $extra = [],
    ): array {
        return $this->call(
            "wall.get",
            array_merge($extra, [
                "owner_id" => $ownerId,
                "count" => $count,
                "offset" => $offset,
            ]),
        );
    }

    /**
     * Обёртка для newsfeed.get
     *
     * @see https://dev.vk.com/method/newsfeed.get
     */
    public function newsfeedGet(
        array $filters = ["post"],
        int $count = 30,
        array $extra = [],
    ): array {
        return $this->call(
            "newsfeed.get",
            array_merge($extra, [
                "filters" => implode(",", $filters),
                "count" => $count,
            ]),
        );
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }
}
