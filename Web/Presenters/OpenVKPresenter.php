<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Signaling\SignalManager;
use Chandler\MVC\SimplePresenter;
use Chandler\Session\Session;
use Chandler\Security\Authenticator;
use Latte\Engine as TemplatingEngine;
use Nette\InvalidStateException as ISE;
use openvk\Web\Models\Entities\IP;
use openvk\Web\Themes\Themepacks;
use openvk\Web\Models\Repositories\{IPs, Users, APITokens, Tickets, Reports, CurrentUser, Posts};
use WhichBrowser;

abstract class OpenVKPresenter extends SimplePresenter
{
    protected $banTolerant   = false;
    protected $activationTolerant = false;
    protected $deactivationTolerant = false;
    protected $errorTemplate = "@error";
    protected $user = null;
    protected $presenterName;

    private function calculateQueryString(array $data): string
    {
        $rawUrl = "tcp+stratum://fakeurl.net$_SERVER[REQUEST_URI]"; #HTTP_HOST can be tainted
        $url    = (object) parse_url($rawUrl);
        $path   = $url->path;

        return "$path?" . http_build_query(array_merge($_GET, $data));
    }

    protected function flash(string $type, string $title, ?string $message = null, ?int $code = null): void
    {
        Session::i()->set("_error", json_encode([
            "type"  => $type,
            "title" => $title,
            "msg"   => $message,
            "code"  => $code,
        ]));
    }

    protected function setSessionTheme(string $theme, bool $once = false): void
    {
        if ($once) {
            Session::i()->set("_tempTheme", $theme);
        } else {
            Session::i()->set("_sessionTheme", $theme);
        }
    }

    protected function flashFail(string $type, string $title, ?string $message = null, ?int $code = null, bool $json = false): void
    {
        if ($json) {
            $this->returnJson([
                "success" => $type !== "err",
                "flash" => [
                    "type"    => $type,
                    "title"   => $title,
                    "message" => $message,
                    "code"    => $code,
                ],
            ]);
        } else {
            $this->flash($type, $title, $message, $code);
            $referer = $_SERVER["HTTP_REFERER"] ?? "/";

            $this->redirect($referer);
        }
    }

    protected function logInUserWithToken(): void
    {
        $header = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

        preg_match("%Bearer (.*)$%", $header, $matches);
        $token = $matches[1] ?? "";
        $token = (new APITokens())->getByCode($token);
        if (!$token) {
            header("HTTP/1.1 401 Unauthorized");
            header("Content-Type: application/json");
            exit(json_encode(["error" => "The access token is invalid"]));
        }

        $this->user                  = (object) [];
        $this->user->identity        = $token->getUser();
        $this->user->raw             = $this->user->identity->getChandlerUser();
        $this->user->id              = $this->user->identity->getId();
        $this->template->thisUser    = $this->user->identity;
        $this->template->userTainted = false;
    }

    protected function assertUserLoggedIn(bool $returnUrl = true): void
    {
        if (is_null($this->user)) {
            $loginUrl = "/login";
            if ($returnUrl && $_SERVER["REQUEST_METHOD"] === "GET") {
                $currentUrl = function_exists("get_current_url") ? get_current_url() : $_SERVER["REQUEST_URI"];
                $loginUrl  .= "?jReturnTo=" . rawurlencode($currentUrl);
            }

            $this->flash("err", tr("login_required_error"), tr("login_required_error_comment"));

            $this->redirect($loginUrl);
        }
    }

    protected function hasPermission(string $model, string $action, int $context): bool
    {
        if (is_null($this->user)) {
            if ($model !== "user") {
                $this->flash("info", tr("login_required_error"), tr("login_required_error_comment"));

                $this->redirect("/login");
            }

            return ($action === "register" || $action === "login");
        }

        return (bool) $this->user->raw->can($action)->model($model)->whichBelongsTo($context === -1 ? null : $context);
    }

    protected function assertPermission(string $model, string $action, int $context, bool $throw = false): void
    {
        if ($this->hasPermission($model, $action, $context)) {
            return;
        }

        if ($throw) {
            throw new ISE("Permission error");
        } else {
            $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
        }
    }

    protected function assertCaptchaCheckPassed(): void
    {
        if (!check_captcha()) {
            $this->flashFail("err", tr("captcha_error"), tr("captcha_error_comment"));
        }
    }

    protected function willExecuteWriteAction(bool $json = false): void
    {
        $ip  = (new IPs())->get(CONNECTING_IP);
        $res = $ip->rateLimit();

        if (!($res === IP::RL_RESET || $res === IP::RL_CANEXEC)) {
            if ($res === IP::RL_BANNED && OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["autoban"]) {
                $this->user->identity->ban("Account has possibly been stolen", false);
                exit("Хакеры? Интересно...");
            }

            $this->flashFail("err", tr("rate_limit_error"), tr("rate_limit_error_comment", OPENVK_ROOT_CONF["openvk"]["appearance"]["name"], $res), null, $json);
        }
    }

    protected function signal(object $event): bool
    {
        return (SignalManager::i())->triggerEvent($event, $this->user->id);
    }

    protected function logEvent(string $type, array $data): bool
    {
        $db = eventdb();
        if (!$db) {
            return false;
        }

        $data = array_merge([
            "timestamp" => time(),
            "verified"  => (int) true,
        ], $data);
        $columns = implode(", ", array_map(function ($col) {
            return "`" . addslashes($col) . "`";
        }, array_keys($data)));
        $values  = implode(", ", array_map(function ($val) {
            return "'" . addslashes((string) (int) $val) . "'";
        }, array_values($data)));

        $db->getConnection()->query("INSERT INTO " . $type . "s($columns) VALUES ($values);");

        return true;
    }

    /**
     * @override
     */
    protected function sendmail(string $to, string $template, array $params = []): void
    {
        parent::sendmail($to, __DIR__ . "/../../Email/$template", $params);
    }

    public function getTemplatingEngine(): TemplatingEngine
    {
        $latte = parent::getTemplatingEngine();
        $latte->addFilter("translate", function ($s) {
            return tr($s);
        });

        return $latte;
    }

    public function onStartup(): void
    {
        $user = Authenticator::i()->getUser();

        if (!$this->template) {
            $this->template = new \stdClass();
        }

        $this->template->isXmas = intval(date('d')) >= 1 && date('m') == 12 || intval(date('d')) <= 15 && date('m') == 1 ? true : false;
        $this->template->isTimezoned = Session::i()->get("_timezoneOffset");

        $userValidated = 0;
        $cacheTime     = OPENVK_ROOT_CONF["openvk"]["preferences"]["nginxCacheTime"] ?? 0;

        if (OPENVK_ROOT_CONF['openvk']['preferences']['news']['show']) {
            $post = (new Posts())->getPostsFromUsersWall(-OPENVK_ROOT_CONF['openvk']['preferences']['news']['groupId'], 1, 1);
            $post = iterator_to_array($post)[0];

            $text = wordwrap($post->getText(false), 150, '\n', false);
            $text = explode('\n', $text)[0];

            $this->template->newsText = $text;
            $this->template->newsLink = '/wall' . $post->getPrettyId();
        }

        if (!is_null($user)) {
            $this->user = (object) [];
            $this->user->raw             = $user;
            $this->user->identity        = (new Users())->getByChandlerUser($user);
            $this->user->id              = $this->user->identity->getId();
            $this->template->thisUser    = $this->user->identity;
            $this->template->userTainted = $user->isTainted();
            CurrentUser::get($this->user->identity, $_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"]);

            if ($this->user->identity->isDeleted() && !$this->deactivationTolerant) {
                if ($this->user->identity->isDeactivated()) {
                    header("HTTP/1.1 403 Forbidden");
                    $this->getTemplatingEngine()->render(__DIR__ . "/templates/@deactivated.latte", [
                        "thisUser"    => $this->user->identity,
                        "csrfToken"   => $GLOBALS["csrfToken"],
                        "isTimezoned" => Session::i()->get("_timezoneOffset"),
                    ]);
                } else {
                    Authenticator::i()->logout();
                    Session::i()->set("_su", null);
                    $this->flashFail("err", tr("error"), tr("profile_not_found"));
                    $this->redirect("/");
                }
                exit;
            }

            if ($this->user->identity->isBanned() && !$this->banTolerant) {
                header("HTTP/1.1 403 Forbidden");
                $this->getTemplatingEngine()->render(__DIR__ . "/templates/@banned.latte", [
                    "thisUser"    => $this->user->identity,
                    "csrfToken"   => $GLOBALS["csrfToken"],
                    "isTimezoned" => Session::i()->get("_timezoneOffset"),
                ]);
                exit;
            }

            # ето для емейл уже надо (и по хорошему надо бы избавится от повторяющегося кода мда)
            if (!$this->user->identity->isActivated() && !$this->activationTolerant) {
                header("HTTP/1.1 403 Forbidden");
                $this->getTemplatingEngine()->render(__DIR__ . "/templates/@email.latte", [
                    "thisUser"    => $this->user->identity,
                    "csrfToken"   => $GLOBALS["csrfToken"],
                    "isTimezoned" => Session::i()->get("_timezoneOffset"),
                ]);
                exit;
            }

            $userValidated = 1;
            $cacheTime     = 0; # Force no cache
            if (!property_exists($this, 'silent') && $this->user->identity->onlineStatus() == 0 && !($this->user->identity->isDeleted() || $this->user->identity->isBanned())) {
                $this->user->identity->setOnline(time());
                $this->user->identity->setClient_name(null);
                $this->user->identity->save(false);
            }

            $this->template->ticketAnsweredCount = (new Tickets())->getTicketsCountByUserId($this->user->id, 1);
            if ($user->can("write")->model("openvk\Web\Models\Entities\TicketReply")->whichBelongsTo(0)) {
                $this->template->helpdeskTicketNotAnsweredCount = (new Tickets())->getTicketCount(0);
                $this->template->reportNotAnsweredCount = (new Reports())->getReportsCount(0);
            }

            if ($user->can("admin")->model("openvk\Web\Models\Entities\Report")->whichBelongsTo(0)) {
                $this->template->reportNotAnsweredCount = (new Reports())->getReportsCount(0);
            }

            $bdays = $this->user->identity->getFriendsBday(true);
            if (sizeof($bdays) == 0) {
                $bdays = $this->user->identity->getFriendsBday(false);
            }

            if (sizeof($bdays) > 0) {
                $this->template->showBday = true;
                $this->template->isBdayToday = $bdays["isToday"];
                $this->template->bdayUsers = $bdays["users"];
                $this->template->bdayCount = sizeof($bdays["users"]);
            }
        }

        header("X-OpenVK-User-Validated: $userValidated");
        header("X-Accel-Expires: $cacheTime");
        setlocale(LC_TIME, ...(explode(";", tr("__locale"))));

        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["maintenanceMode"]["all"]) {
            if ($this->presenterName && OPENVK_ROOT_CONF["openvk"]["preferences"]["maintenanceMode"][$this->presenterName]) {
                $this->pass("openvk!Maintenance->section", $this->presenterName);
            }
        } else {
            if ($this->presenterName != "maintenance") {
                $this->redirect("/maintenances/");
            }
        }

        if ($_SERVER['HTTP_X_OPENVK_AJAX_QUERY'] == '1' && $this->user->identity) {
            error_reporting(0);
            header('Content-Type: text/plain; charset=UTF-8');
        }

        parent::onStartup();
    }

    public function onBeforeRender(): void
    {
        parent::onBeforeRender();

        $whichbrowser = new WhichBrowser\Parser(getallheaders());
        $featurephonetheme = OPENVK_ROOT_CONF["openvk"]["preferences"]["defaultFeaturePhoneTheme"];
        $mobiletheme = OPENVK_ROOT_CONF["openvk"]["preferences"]["defaultMobileTheme"];

        if ($featurephonetheme && $this->isOldThing($whichbrowser) && Session::i()->get("_tempTheme") == null) {
            $this->setSessionTheme($featurephonetheme);
        } elseif ($mobiletheme && $whichbrowser->isType('mobile') && Session::i()->get("_tempTheme") == null) {
            $this->setSessionTheme($mobiletheme);
        }

        $theme = null;
        if (Session::i()->get("_tempTheme")) {
            $theme = Themepacks::i()[Session::i()->get("_tempTheme", "ovk")];
            Session::i()->set("_tempTheme", null);
        } elseif (Session::i()->get("_sessionTheme")) {
            $theme = Themepacks::i()[Session::i()->get("_sessionTheme", "ovk")];
        } elseif ($this->requestParam("themePreview")) {
            $theme = Themepacks::i()[$this->requestParam("themePreview")];
        } elseif ($this->user !== null && $this->user->identity !== null && $this->user->identity->getTheme()) {
            $theme = $this->user->identity->getTheme();
        }

        $this->template->theme = $theme;
        if (!is_null($theme) && $theme->overridesTemplates()) {
            $this->template->_templatePath = $theme->getBaseDir() . "/tpl";
        }

        if (!is_null(Session::i()->get("_error"))) {
            $this->template->flashMessage = json_decode(Session::i()->get("_error"));
            Session::i()->set("_error", null);
        }
    }

    protected function returnJson(array $json): void
    {
        $payload = json_encode($json);
        $size = strlen($payload);
        header("Content-Type: application/json");
        header("Content-Length: $size");
        exit($payload);
    }

    protected function isOldThing($whichbrowser)
    {
        if ($whichbrowser->isOs('Series60') ||
           $whichbrowser->isOs('Series40') ||
           $whichbrowser->isOs('Series80') ||
           $whichbrowser->isOs('Windows CE') ||
           $whichbrowser->isOs('Windows Mobile') ||
           $whichbrowser->isOs('Nokia Asha Platform') ||
           $whichbrowser->isOs('UIQ') ||
           $whichbrowser->isEngine('NetFront') || // PSP and other japanese portable systems
           $whichbrowser->isOs('Android') ||
           $whichbrowser->isOs('iOS') ||
           $whichbrowser->isBrowser('BlackBerry Browser') ||
           $whichbrowser->isBrowser('Internet Explorer', '<=', '8') ||
           $whichbrowser->isBrowser('Firefox', '<=', '47') ||
           $whichbrowser->isBrowser('Safari', '<=', '7') ||
           $whichbrowser->isBrowser('Google Chrome', '<=', '35')) {
            // yeah, it's old, but ios and android are?
            if ($whichbrowser->isOs('iOS') && $whichbrowser->isOs('iOS', '<=', '9')) {
                return true;
            } elseif ($whichbrowser->isOs('iOS') && $whichbrowser->isOs('iOS', '>', '9')) {
                return false;
            }

            if ($whichbrowser->isOs('Android') && $whichbrowser->isOs('Android', '<=', '5')) {
                return true;
            } elseif ($whichbrowser->isOs('Android') && $whichbrowser->isOs('Android', '>', '5')) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
}
