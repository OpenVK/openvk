<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use Chandler\Signaling\SignalManager;
use Chandler\MVC\SimplePresenter;
use Chandler\Session\Session;
use Chandler\Security\Authenticator;
use Latte\Engine as TemplatingEngine;
use openvk\Web\Models\Entities\IP;
use openvk\Web\Models\Repositories\{IPs, Users, APITokens};

abstract class OpenVKPresenter extends SimplePresenter
{
    protected $banTolerant   = false;
    protected $errorTemplate = "@error";
    protected $user = NULL;
    
    private function calculateQueryString(array $data): string
    {
        $rawUrl = "tcp+stratum://fakeurl.net$_SERVER[REQUEST_URI]"; #HTTP_HOST can be tainted
        $url    = (object) parse_url($rawUrl);
        $path   = $url->path;
        
        return "$path?" . http_build_query(array_merge($_GET, $data));
    }
    
    protected function flash(string $type, string $title, ?string $message = NULL, ?int $code = NULL): void
    {
        Session::i()->set("_error", json_encode([
            "type"  => $type,
            "title" => $title,
            "msg"   => $message,
            "code"  => $code,
        ]));
    }
    
    protected function flashFail(string $type, string $title, ?string $message = NULL, ?int $code = NULL): void
    {
        $this->flash($type, $title, $message, $code);
        $referer = $_SERVER["HTTP_REFERER"] ?? "/";
        
        header("HTTP/1.1 302 Found");
        header("Location: $referer");
        exit;
    }
    
    protected function logInUserWithToken(): void
    {
        $header = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
        $token;
        
        preg_match("%Bearer (.*)$%", $header, $matches);
        $token = $matches[1] ?? "";
        $token = (new APITokens)->getByCode($token);
        if(!$token) {
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
        if(is_null($this->user)) {
            $loginUrl = "/login";
            if($returnUrl && $_SERVER["REQUEST_METHOD"] === "GET") {
                $currentUrl = function_exists("get_current_url") ? get_current_url() : $_SERVER["REQUEST_URI"];
                $loginUrl  .= "?jReturnTo=" . rawurlencode($currentUrl);
            }
            
            $this->flash("err", "Недостаточно прав", "Чтобы просматривать эту страницу, нужно зайти на сайт.");
            header("HTTP/1.1 302 Found");
            header("Location: $loginUrl");
            exit;
        }
    }
    
    protected function hasPermission(string $model, string $action, int $context): bool
    {
        if(is_null($this->user)) {
            if($model !== "user") {
                $this->flash("info", "Недостаточно прав", "Чтобы просматривать эту страницу, нужно зайти на сайт.");
                
                header("HTTP/1.1 302 Found");
                header("Location: /login");
                exit;
            }
            
            return ($action === "register" || $action === "login");
        }
        
        return (bool) $this->user->raw->can($action)->model($model)->whichBelongsTo($context === -1 ? null : $context);
    }
    
    protected function assertPermission(string $model, string $action, int $context, bool $throw = false): void
    {
        if($this->hasPermission($model, $action, $context)) return;
        
        if($throw)
            throw new SecurityPolicyViolationException("Permission error");
        else
            $this->flashFail("err", "Недостаточно прав", "У вас недостаточно прав чтобы выполнять это действие.");
    }
    
    protected function assertCaptchaCheckPassed(): void
    {
        if(!check_captcha())
            $this->flashFail("err", "Неправильно введены символы", "Пожалуйста, убедитесь, что вы правильно заполнили поле с капчей.");
    }
    
    protected function willExecuteWriteAction(): void
    {
        $ip  = (new IPs)->get(CONNECTING_IP);
        $res = $ip->rateLimit();
        
        if(!($res === IP::RL_RESET || $res === IP::RL_CANEXEC)) {
            if($res === IP::RL_BANNED && OPENVK_ROOT_CONF["openvk"]["preferences"]["security"]["rateLimits"]["autoban"]) {
                $this->user->identity->ban("Account has possibly been stolen");
                exit("Хакеры? Интересно...");
            }
            
            $this->flashFail("err", "Чумба, ты совсем ёбнутый?", "Сходи к мозгоправу, попей колёсики. В OpenVK нельзя вбрасывать щитпосты так часто. Код исключения: $res.");
        }
    }
    
    protected function signal(object $event): bool
    {
        return (SignalManager::i())->triggerEvent($event, $this->user->id);
    }
    
    protected function logEvent(string $type, array $data): bool
    {
        $db = eventdb();
        if(!$db)
            return false;
        
        $data = array_merge([
            "timestamp" => time(),
            "verified"  => (int) true,
        ], $data);
        $columns = implode(", ", array_map(function($col) {
            return "`" . addslashes($col) . "`";
        }, array_keys($data)));
        $values  = implode(", ", array_map(function($val) {
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
    
    function getTemplatingEngine(): TemplatingEngine
    {
        $latte = parent::getTemplatingEngine();
        $latte->addFilter("translate", function($s) {
            return tr($s);
        });
        
        return $latte;
    }
    
    function onStartup(): void
    {
        $user = Authenticator::i()->getUser();
        
        $this->template->isXmas = intval(date('d')) >= 15 && date('m') == 12 || intval(date('d')) <= 15 && date('m') == 1 ? true : false;
        
        if(!is_null($user)) {
            $this->user = (object) [];
            $this->user->raw             = $user;
            $this->user->identity        = (new Users)->getByChandlerUser($user);
            $this->user->id              = $this->user->identity->getId();
            $this->template->thisUser    = $this->user->identity;
            $this->template->userTainted = $user->isTainted();
            
            if($this->user->identity->isBanned() && !$this->banTolerant) {
                header("HTTP/1.1 403 Forbidden");
                $this->getTemplatingEngine()->render(__DIR__ . "/templates/@banned.xml", [
                    "thisUser" => $this->user->identity,
                ]);
                exit;
            }
            
            if ($this->user->identity->onlineStatus() == 0) {
                $this->user->identity->setOnline(time());
                $this->user->identity->save();
            }
            
        }
        
        setlocale(LC_TIME, ...(explode(";", tr("__locale"))));
        
        parent::onStartup();
    }
    
    function onBeforeRender(): void
    {
        parent::onBeforeRender();
        
        if(!is_null(Session::i()->get("_error"))) {
            $this->template->flashMessage = json_decode(Session::i()->get("_error"));
            Session::i()->set("_error", NULL);
        }
    }
} 
