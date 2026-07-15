<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Security\Authenticator;
use Chandler\Database\DatabaseConnection as DB;
use openvk\VKAPI\Exceptions\APIErrorException;
use openvk\Web\Models\Entities\{User, APIToken};
use openvk\Web\Models\Repositories\{Users, APITokens};
use lfkeitel\phptotp\{Base32, Totp};
use WhichBrowser;

final class VKAPIPresenter extends OpenVKPresenter
{
    protected $silent = true;
    private function logRequest(string $object, string $method): void
    {
        $date   = date(DATE_COOKIE);
        $params = json_encode($_REQUEST);
        $log    = "[$date] $object.$method called with $params\r\n";
        file_put_contents(OPENVK_ROOT . "/VKAPI/debug.log", $log, FILE_APPEND | LOCK_EX);
    }

    private function fail(int $code, string $message, string $object, string $method): void
    {
        header("HTTP/1.1 400 Bad API Call");
        header("Content-Type: application/json");

        $payload = [
            "error_code"     => $code,
            "error_msg"      => $message,
            "request_params" => [
                [
                    "key"   => "method",
                    "value" => "$object.$method",
                ],
                [
                    "key"   => "oauth",
                    "value" => 1,
                ],
            ],
        ];

        foreach ($_GET as $key => $value) {
            array_unshift($payload["request_params"], [ "key" => $key, "value" => $value ]);
        }

        exit(json_encode($payload));
    }

    private function twofaFail(int $userId, string $data): void
    {
        header("HTTP/1.1 401 Unauthorized");
        header("Content-Type: application/json");

        $payload = [
            "error"             => "need_validation",
            "error_description" => "use app code",
            "validation_type"   => "2fa_app",
            "validation_sid"    => "2fa_" . $userId . "_2839041_randommessdontread",
            "phone_mask"        => "+374 ** *** 420",
            "redirect_uri"      => ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/2fa?data=" . base64_encode($data),
            "validation_resend" => "nowhere",
        ];

        exit(json_encode($payload));
    }

    private function badMethod(string $object, string $method): void
    {
        $this->fail(3, "Unknown method passed.", $object, $method);
    }

    private function badMethodCall(string $object, string $method, string $param): void
    {
        $this->fail(100, "Required parameter '$param' missing.", $object, $method);
    }

    public function onStartup(): void
    {
        parent::onStartup();

        # idk, but in case we will ever support non-standard HTTP credential authflow
        $origin = "*";
        if (isset($_SERVER["HTTP_REFERER"])) {
            $refOrigin = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_SCHEME) . "://" . parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
            if ($refOrigin !== false) {
                $origin = $refOrigin;
            }
        }

        if (!is_null($this->queryParam("requestPort"))) {
            $origin .= ":" . ((int) $this->queryParam("requestPort"));
        }

        header("Access-Control-Allow-Origin: $origin");

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            header("Access-Control-Allow-Methods: POST, PUT, DELETE");
            header("Access-Control-Allow-Headers: " . $_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]);
            header("Access-Control-Max-Age: -1");
            exit; # Terminate request processing as this is definitely a CORS preflight request.
        }
    }

    public function renderPhotoUpload(string $signature): void
    {
        $secret            = CHANDLER_ROOT_CONF["security"]["secret"];
        $queryString       = rawurldecode($_SERVER["QUERY_STRING"]);
        $computedSignature = hash_hmac("sha3-224", $queryString, $secret);
        if (!(strlen($signature) == 56 && sodium_memcmp($signature, $computedSignature) == 0)) {
            header("HTTP/1.1 422 Unprocessable Entity");
            exit("Try harder <3");
        }

        $data = unpack("vDOMAIN/Z10FIELD/vMF/vMP/PTIME/PUSER/PGROUP", base64_decode($queryString));
        if ((time() - $data["TIME"]) > 600) {
            header("HTTP/1.1 422 Unprocessable Entity");
            exit("Expired");
        }

        $folder   = __DIR__ . "/../../tmp/api-storage/photos";
        $maxSize  = OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["api"]["maxFileSize"];
        $maxFiles = OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["api"]["maxFilesPerDomain"];

        $usrFiles = sizeof(glob("$folder/$data[USER]_*.oct"));
        if ($usrFiles >= $maxFiles) {
            $usrFiles = $this->evictOldestPendingUploads($folder, (string) $data["USER"], $maxFiles);

            if ($usrFiles >= $maxFiles) {
                $pendingInfo = $this->getPendingUploadInfo($folder, $data["USER"]);

                header("HTTP/1.1 507 Insufficient Storage");
                header("Content-Type: application/json");
                exit(json_encode([
                    "error" => "insufficient_storage",
                    "error_description" => "There are $maxFiles pending already. Please save them before uploading more :3",
                    "pending_uploads" => $pendingInfo,
                ]));
            }
        }

        # Not multifile
        if ($data["MF"] === 0) {
            $file = $_FILES[$data["FIELD"]];

            if (!$file) {
                header("HTTP/1.0 400");
                exit("No file");
            } elseif ($file["error"] != UPLOAD_ERR_OK) {
                header("HTTP/1.0 500");
                exit("File could not be consumed");
            } elseif ($file["size"] > $maxSize) {
                header("HTTP/1.0 507 Insufficient Storage");
                exit("File is too big");
            }
            
            $slot = $this->getNextUploadSlot($folder, (string) $data["USER"]);
            if (!move_uploaded_file($file["tmp_name"], "$folder/$data[USER]_$slot.oct")) {
                header("HTTP/1.0 500");
                exit("File could not be saved");
            }
            header("HTTP/1.0 202 Accepted");

            $photo = $data["USER"] . "|" . $slot . "|" . $data["GROUP"];
            exit(json_encode([
                "server" => "ephemeral",
                "photo"  => $photo,
                "hash"   => hash_hmac("sha3-224", $photo, $secret),
            ]));
        }

        $files = [];
        $slot  = $this->getNextUploadSlot($folder, (string) $data["USER"]);
        for ($i = 1; $i <= 5; $i++) {
            $file = $_FILES[$data["FIELD"] . $i] ?? null;
            if (!$file || $file["error"] != UPLOAD_ERR_OK || $file["size"] > $maxSize) {
                continue;
            } elseif ((sizeof($files) + $usrFiles) >= $maxFiles) {
                $usrFiles = $this->evictOldestPendingUploads($folder, (string) $data["USER"], $maxFiles, array_keys($files));

                if ((sizeof($files) + $usrFiles) >= $maxFiles) {
                    foreach ($files as $id => $f) {
                        @unlink("$folder/$data[USER]_$id.oct");
                    }

                    $pendingInfo = $this->getPendingUploadInfo($folder, $data["USER"]);

                    header("HTTP/1.1 507 Insufficient Storage");
                    header("Content-Type: application/json");
                    exit(json_encode([
                        "error" => "insufficient_storage",
                        "error_description" => "There are $maxFiles pending already. Please save them before uploading more :3",
                        "pending_uploads" => $pendingInfo,
                    ]));
                }
            }

            if (move_uploaded_file($file["tmp_name"], "$folder/$data[USER]_$slot.oct")) {
                $files[$slot] = true;
                $slot++;
            }
        }

        if (sizeof($files) === 0) {
            header("HTTP/1.0 400");
            exit("No file");
        }

        $filesManifest = [];
        foreach ($files as $id => $file) {
            $filesManifest[] = ["keyholder" => $data["USER"], "resource" => $id, "club" => $data["GROUP"]];
        }

        $filesManifest = json_encode($filesManifest);
        $manifestHash  = hash_hmac("sha3-224", $filesManifest, $secret);
        header("HTTP/1.0 202 Accepted");
        exit(json_encode([
            "server"      => "ephemeral",
            "photos_list" => $filesManifest,
            "album_id"    => "undefined",
            "hash"        => $manifestHash,
        ]));
    }

    private function evictOldestPendingUploads(string $folder, string $userId, int $maxFiles, array $protectedSlots = []): int
    {
        $files = [];

        foreach (glob("$folder/{$userId}_*.oct") as $file) {
            if (!preg_match("/_(\\d+)\\.oct$/", basename($file), $matches)) {
                continue;
            }

            $slot = (int) $matches[1];
            if (in_array($slot, $protectedSlots, true)) {
                continue;
            }

            $mtime = @filemtime($file);
            $files[] = ["path" => $file, "mtime" => $mtime === false ? 0 : $mtime];
        }

        usort($files, fn ($a, $b) => $a["mtime"] <=> $b["mtime"]);

        $count = sizeof($files) + sizeof($protectedSlots);

        foreach ($files as $file) {
            if ($count < $maxFiles) {
                break;
            }

            if (@unlink($file["path"])) {
                $count--;
            }
        }

        return $count;
    }

    private function getNextUploadSlot(string $folder, string $userId): int
    {
        $slot = 0;

        foreach (glob("$folder/{$userId}_*.oct") as $file) {
            if (preg_match("/_(\\d+)\\.oct$/", basename($file), $matches)) {
                $slot = max($slot, (int) $matches[1]);
            }
        }

        return $slot + 1;
    }

    private function getPendingUploadInfo(string $folder, string $userId): array
    {
        $pendingFiles = glob("$folder/$userId" . "_*.oct");
        $pendingInfo = [];

        foreach ($pendingFiles as $file) {
            $filename = basename($file);
            $uploadId = str_replace([$userId . "_", ".oct"], "", $filename);
            $fileTime = filemtime($file);
            $fileSize = filesize($file);
            $ageHours = round((time() - $fileTime) / 3600, 1);

            $pendingInfo[] = [
                "upload_id" => $uploadId,
                "filename" => $filename,
                "size" => $fileSize,
                "age_hours" => $ageHours,
                "uploaded_at" => date("Y-m-d H:i:s", $fileTime),
            ];
        }

        return $pendingInfo;
    }

    /**
     * Resolves the calling identity (and client platform) from the request, exactly as the
     * normal API entrypoint does. On authorization problems it emits an error and exits.
     *
     * @return array{0: ?User, 1: ?string} [identity, platform]
     */
    private function resolveIdentity(string $object, string $method): array
    {
        $authMechanism = $this->queryParam("auth_mechanism") ?? "token";
        if ($authMechanism === "roaming") {
            if ($this->queryParam("callback")) {
                $this->fail(-1, "User authorization failed: roaming mechanism is unavailable with jsonp.", $object, $method);
            }

            if (!$this->user->identity) {
                $this->fail(5, "User authorization failed: roaming mechanism is selected, but user is not logged in.", $object, $method);
            }

            $identity = $this->user->identity;
            $platform = null;
        } else {
            $identity = null;
            $platform = null;
            if (!is_null($this->requestParam("access_token"))) {
                $token = (new APITokens())->getByCode($this->requestParam("access_token"));
                if ($token) {
                    $identity = $token->getUser();
                    $platform = $token->getPlatform();
                }
            } elseif (!is_null($_SERVER['HTTP_AUTHORIZATION'])) {
                $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
                $token = (new APITokens())->getByCode($token);
                if ($token) {
                    $identity = $token->getUser();
                    $platform = $token->getPlatform();
                }
            }
        }

        if (!is_null($identity) && ($identity->isBanned() || $identity->isDeleted())) {
            $this->fail(18, "User account is deactivated", $object, $method);
        }

        return [$identity, $platform];
    }

    /**
     * Instantiates the handler for $object, binds $params (name => value) to the target
     * method's signature and invokes it, returning the raw result. Reused by both the normal
     * API entrypoint and the `execute` method. Errors are thrown as APIErrorException
     * (unknown method => 3, missing required param => 100) rather than emitted directly.
     *
     * @param array<string, mixed> $params
     */
    private function callAPIMethod(string $object, string $method, array $params, $identity, $platform, ?bool &$hasRss = null)
    {
        $object       = ucfirst(strtolower($object));
        $handlerClass = "openvk\\VKAPI\\Handlers\\$object";
        if (!class_exists($handlerClass)) {
            throw new APIErrorException("Unknown method passed.", 3);
        }

        $handler = new $handlerClass($identity, $platform);
        if (!is_callable([$handler, $method])) {
            throw new APIErrorException("Unknown method passed.", 3);
        }

        $hasRss = false;
        $route  = new \ReflectionMethod($handler, $method);
        $args   = [];
        foreach ($route->getParameters() as $parameter) {
            if ($parameter->getName() == 'rss') {
                $hasRss = true;
            }

            $val = $params[$parameter->getName()] ?? null;
            if (is_null($val)) {
                if ($parameter->allowsNull()) {
                    $val = null;
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $val = $parameter->getDefaultValue();
                } elseif ($parameter->isOptional()) {
                    $val = null;
                } else {
                    throw new APIErrorException("Required parameter '" . $parameter->getName() . "' missing.", 100);
                }
            }

            try {
                // Проверка типа параметра
                $type = $parameter->getType();
                if (($type && !$type->isBuiltin()) || is_null($val)) {
                    $args[] = $val;
                } else {
                    settype($val, $parameter->getType()->getName());
                    $args[] = $val;
                }
            } catch (\Throwable $e) {
                // Just ignore the exception, since
                // some args are intended for internal use
            }
        }

        if (!defined("VKAPI_DECL_VER")) {
            $version = $this->requestParam("v") ?? "5.9999"; // 9999 for ovk apps
            define("VKAPI_DECL_VER", $version);
            define("VKAPI_DECL_VER_MAJOR", intval(explode('.', $version)[0] ?? "5"));
            define("VKAPI_DECL_VER_MINOR", intval(explode('.', $version)[1] ?? "100"));
        }

        return $handler->{$method}(...$args);
    }

    public function renderRoute(string $object, string $method): void
    {
        $callback = $this->queryParam("callback");
        [$identity, $platform] = $this->resolveIdentity($object, $method);

        $has_rss = false;
        try {
            $res = $this->callAPIMethod($object, $method, $_REQUEST, $identity, $platform, $has_rss);
        } catch (APIErrorException $ex) {
            $this->fail($ex->getCode(), $ex->getMessage(), $object, $method);
        }

        $result = null;

        if ($this->queryParam("rss") == '1' && $has_rss) {
            $feed = new \Bhaktaraz\RSSGenerator\Feed();
            $res->appendTo($feed);

            $result = strval($feed);

            header("Content-Type: application/rss+xml;charset=UTF-8");
        } else {
            $result = json_encode([
                "response" => $res,
            ]);

            if ($callback) {
                $result = $callback . '(' . $result . ')';
                header('Content-Type: application/javascript');
            } else {
                header("Content-Type: application/json");
            }
        }

        $size = strlen($result);
        header("Content-Length: $size");

        exit($result);
    }

    public function renderExecute(): void
    {
        $callback = $this->queryParam("callback");
        [$identity, $platform] = $this->resolveIdentity("execute", "");

        $code = $this->requestParam("code");
        if (is_null($code)) {
            $this->fail(100, "Required parameter 'code' missing.", "execute", "");
        }

        // Everything except the reserved keys is exposed to the script via Args.
        $reserved = ["code", "access_token", "v", "callback", "auth_mechanism", "requestPort"];
        $args     = [];
        foreach ($_REQUEST as $key => $value) {
            if (!in_array($key, $reserved, true)) {
                $args[$key] = $value;
            }
        }

        try {
            $tokens = (new \openvk\VKAPI\VKScript\Lexer($code))->tokenize();
            $ast    = (new \openvk\VKAPI\VKScript\Parser($tokens))->parse();

            $interpreter = new \openvk\VKAPI\VKScript\Interpreter(
                function (string $object, string $method, array $params) use ($identity, $platform) {
                    return $this->callAPIMethod($object, $method, $params, $identity, $platform);
                },
                $args
            );

            $res    = $interpreter->run($ast);
            $errors = $interpreter->getExecuteErrors();
        } catch (APIErrorException $ex) {
            $this->fail($ex->getCode(), $ex->getMessage(), "execute", "");
        }

        $payload = ["response" => $res];
        if (!empty($errors)) {
            $payload["execute_errors"] = $errors;
        }

        $result = json_encode($payload);
        if ($callback) {
            $result = $callback . '(' . $result . ')';
            header('Content-Type: application/javascript');
        } else {
            header("Content-Type: application/json");
        }

        $size = strlen($result);
        header("Content-Length: $size");

        exit($result);
    }

    public function renderTokenLogin(): void
    {
        if ($this->requestParam("grant_type") !== "password") {
            $this->fail(7, "Invalid grant type", "internal", "acquireToken");
        } elseif (is_null($this->requestParam("username")) || is_null($this->requestParam("password"))) {
            $this->fail(100, "Password and username not passed", "internal", "acquireToken");
        }

        $chUser = DB::i()->getContext()->table("ChandlerUsers")->where("login", $this->requestParam("username"))->fetch();
        if (!$chUser) {
            $this->fail(28, "Invalid username or password", "internal", "acquireToken");
        }

        $auth = Authenticator::i();
        if (!$auth->verifyCredentials($chUser->id, $this->requestParam("password"))) {
            $this->fail(28, "Invalid username or password", "internal", "acquireToken");
        }

        $uId  = $chUser->related("profiles.user")->fetch()->id;
        $user = (new Users())->get($uId);

        $platform     = $this->requestParam("client_name");
        $platform   ??= $this->resolveAppIdToString($this->requestParam("client_id"));

        $code = $this->requestParam("code");
        if ($user->is2faEnabled() && !($code === (new Totp())->GenerateToken(Base32::decode($user->get2faSecret())) || $user->use2faBackupCode((int) $code))) {
            if (empty($code)) {
                $data = (object) [
                    "login" => $this->requestParam("username"),
                    "password" => $this->requestParam("password"),
                    "client_name" => $platform,
                ];
                $this->twofaFail($user->getId(), json_encode($data));
            } else {
                $this->fail(28, "Invalid 2FA code", "internal", "acquireToken");
            }
        }

        $token        = null;
        $tokenIsStale = true;
        $acceptsStale = $this->requestParam("accepts_stale");
        if ($acceptsStale == "1") {
            if (is_null($platform)) {
                $this->fail(101, "accepts_stale can only be used with explicitly set client_name", "internal", "acquireToken");
            }

            $token = (new APITokens())->getStaleByUser($uId, $platform);
        }

        if (is_null($token)) {
            $tokenIsStale = false;

            $token = new APIToken();
            $token->setUser($user);
            $token->setPlatform($platform ?? (new WhichBrowser\Parser(getallheaders()))->toString());
            $token->save();
        }

        $payload = json_encode([
            "access_token" => $token->getFormattedToken(),
            "expires_in"   => 0,
            "user_id"      => $uId,
            "is_stale"     => $tokenIsStale,
            "secret"       => "super_secret_value",
        ]);

        $size = strlen($payload);
        header("Content-Type: application/json");
        header("Content-Length: $size");
        exit($payload);
    }

    public function renderOAuthLogin()
    {
        $this->assertUserLoggedIn();

        $client  = $this->queryParam("client_name");
        $postmsg = $this->queryParam("prefers_postMessage") ?? '0';
        $stale   = $this->queryParam("accepts_stale") ?? '0';
        $origin  = null;
        $url     = $this->queryParam("redirect_uri");
        $responseType = $this->queryParam("response_type") ?? 'php';

        if (!empty($this->queryParam("client_id")) && empty($client)) {
            $client = $this->resolveAppIdToString($this->queryParam("client_id"));
        }

        if (is_null($url) || is_null($client)) {
            exit("<b>Error:</b> redirect_uri and client_name (or client_id) params are required.");
        }

        if ($url != "about:blank") {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                exit("<b>Error:</b> Invalid URL passed to redirect_uri.");
            }

            $parsedUrl = (object) parse_url($url);
            if ($parsedUrl->scheme != 'https' && $parsedUrl->scheme != 'http') {
                exit("<b>Error:</b> redirect_uri should either point to about:blank or to a web resource.");
            }

            $origin = "$parsedUrl->scheme://$parsedUrl->host";
            if (!is_null($parsedUrl->port ?? null)) {
                $origin .= ":$parsedUrl->port";
            }

            $url .= strpos($url, '?') === false ? '?' : '&';
        } else {
            $url .= "#";
            if ($postmsg == '1') {
                exit("<b>Error:</b> prefers_postMessage can only be set if redirect_uri is not about:blank");
            }
        }

        if (!in_array($responseType, ['php', 'token'])) {
            exit("<b>Error:</b> response_type can equal 'php' or 'token' only.");
        }

        $this->template->clientName     = $client;
        $this->template->usePostMessage = $postmsg == '1';
        $this->template->acceptsStale   = $stale == '1';
        $this->template->origin         = $origin;
        $this->template->redirectUri    = $url;
        $this->template->responseType   = $responseType;
    }

    public function renderTwoFactorLogin()
    {
        $base64 = $this->requestParam("data");
        if (empty($base64)) {
            exit("<b>Error:</b> Empty request.");
        }

        $decoded = base64_decode($base64);

        if ($decoded == false) {
            exit("<b>Error:</b> Invalid base64 data.");
        }

        $parsed = json_decode($decoded);

        if (!is_array($parsed) && empty($parsed->login) && empty($parsed->password) && empty($parsed->client_name)) {
            exit("<b>Error:</b> Invalid login data.");
        }

        $chUser = DB::i()->getContext()->table("ChandlerUsers")->where("login", $parsed->login)->fetch();
        if (!$chUser) {
            exit("<b>Error:</b> Invalid login and password.");
        }

        $auth = Authenticator::i();
        if (!$auth->verifyCredentials($chUser->id, $parsed->password)) {
            exit("<b>Error:</b> Invalid login and password.");
        }

        $uId  = $chUser->related("profiles.user")->fetch()->id;
        $user = (new Users())->get($uId);
        $platform = $parsed->client_name;

        $this->template->base64 = $base64;
        $this->template->platform = $platform;

        $code = $this->requestParam("code");
        if ($user->is2faEnabled() && empty($code)) {
            // intended
        } elseif ($user->is2faEnabled() && !empty($code)) {
            if ($code === (new Totp())->GenerateToken(Base32::decode($user->get2faSecret())) || !empty($user->use2faBackupCode((int) $code))) {
                $token = new APIToken();
                $token->setUser($user);
                $token->setPlatform($platform ?? "api"); // since this is a browser we will just throw "api"
                $token->save();
                $this->redirect('/blank.html#access_token=' . $token->getFormattedToken() . '&expires_in=0&user_id=' . $uId);
            } else {
                $this->flashFail("err", tr('incorrect_code'), tr('incorrect_2fa_code'));
            }
        } else {
            $token = new APIToken();
            $token->setUser($user);
            $token->setPlatform($platform ?? "api");
            $token->save();
            $this->redirect('/blank.html#access_token=' . $token->getFormattedToken() . '&expires_in=0&user_id=' . $uId);
        }
    }

    private function resolveAppIdToString(?string $id = ""): ?string
    {
        switch ($id) {
            case '4083558':
                return "VFeed";
            case '2685278':
                return "Kate Mobile";
            case '3680547':
                return "VK for iOS";
            case '2274003':
                return "VK for Android";
            default:
                return "unknown";
        }
    }
}
