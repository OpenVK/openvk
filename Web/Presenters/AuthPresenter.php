<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\IP;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\PasswordReset;
use openvk\Web\Models\Repositories\IPs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Restores;
use Chandler\Session\Session;
use Chandler\Security\User as ChandlerUser;
use Chandler\Security\Authenticator;
use Chandler\Database\DatabaseConnection;

final class AuthPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    
    private $authenticator;
    private $db;
    private $users;
    private $restores;
    
    function __construct(Users $users, Restores $restores)
    {
        $this->authenticator = Authenticator::i();
        $this->db = DatabaseConnection::i()->getContext();
        
        $this->users    = $users;
        $this->restores = $restores;
        
        parent::__construct();
    }
    
    private function emailValid(string $email): bool
    {
        if(empty($email)) return false;
        
        $email = trim($email);
        [$user, $domain] = explode("@", $email);
        $domain = idn_to_ascii($domain) . ".";
        
        return checkdnsrr($domain, "MX");
    }
    
    private function ipValid(): bool
    {
        $ip  = (new IPs)->get(CONNECTING_IP);
        $res = $ip->rateLimit(0);
        
        return $res === IP::RL_RESET || $res === IP::RL_CANEXEC;
    }
    
    function renderRegister(): void
    {
        if(!is_null($this->user))
            $this->redirect("/id" . $this->user->id, static::REDIRECT_TEMPORARY);
        
        if(!$this->hasPermission("user", "register", -1)) exit("Вас забанили");
        
        $referer = NULL;
        if(!is_null($refLink = $this->queryParam("ref"))) {
            $pieces = explode(" ", $refLink, 2);
            if(sizeof($pieces) !== 2)
                $this->flashFail("err", "Пригласительная ссылка кривая", "Пригласительная ссылка недействительна.");
            
            [$ref, $hash] = $pieces;
            $ref  = hexdec($ref);
            $hash = base64_decode($hash);
            
            $referer = (new Users)->get($ref);
            if(!$referer)
                $this->flashFail("err", "Пригласительная ссылка кривая", "Пригласительная ссылка недействительна.");
            
            if($referer->getRefLinkId() !== $refLink)
                $this->flashFail("err", "Пригласительная ссылка кривая", "Пригласительная ссылка недействительна.");
        }
        
        $this->template->referer = $referer;
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertCaptchaCheckPassed();
            
            if(!$this->ipValid())
                $this->flashFail("err", "Подозрительная попытка регистрации", "Вы пытались зарегистрироваться из подозрительного места.");
            
            if(!$this->emailValid($this->postParam("email")))
                $this->flashFail("err", "Неверный email адрес", "Email, который вы ввели, не является корректным.");
            
            $chUser = ChandlerUser::create($this->postParam("email"), $this->postParam("password"));
            if(!$chUser)
                $this->flashFail("err", "Не удалось зарегистрироваться", "Пользователь с таким email уже существует.");
            
            $user = new User;
            $user->setUser($chUser->getId());
            $user->setFirst_Name($this->postParam("first_name"));
            $user->setLast_Name($this->postParam("last_name"));
            $user->setSex((int) ($this->postParam("sex") === "female"));
            $user->setEmail($this->postParam("email"));
            $user->setSince(date("Y-m-d H:i:s"));
            $user->setRegistering_Ip(CONNECTING_IP);
            $user->save();
            
            if(!is_null($referer)) {
                $user->toggleSubscription($referer);
                $referer->toggleSubscription($user);
            }
            
            $this->authenticator->authenticate($chUser->getId());
            $this->redirect("/id" . $user->getId(), static::REDIRECT_TEMPORARY);
        }
    }
    
    function renderLogin(): void
    {
        $redirUrl = $this->requestParam("jReturnTo");
        
        if(!is_null($this->user))
            $this->redirect($redirUrl ?? "/id" . $this->user->id, static::REDIRECT_TEMPORARY);
        
        if(!$this->hasPermission("user", "login", -1)) exit("Вас забанили");
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            
            $user = $this->db->table("ChandlerUsers")->where("login", $this->postParam("login"))->fetch();
            if(!$user)
                $this->flashFail("err", "Не удалось войти", "Неверное имя пользователя или пароль. <a href='/restore.pl'>Забыли пароль?</a>");
            
            if(!$this->authenticator->login($user->id, $this->postParam("password")))
                $this->flashFail("err", "Не удалось войти", "Неверное имя пользователя или пароль. <a href='/restore.pl'>Забыли пароль?</a>");
            
            $this->redirect($redirUrl ?? "/id" . $user->related("profiles.user")->fetch()->id, static::REDIRECT_TEMPORARY);
            exit;
        }
    }
    
    function renderSu(string $uuid): void
    {
        $this->assertNoCSRF();
        $this->assertUserLoggedIn();
        
        if($uuid === "unset") {
            Session::i()->set("_su", NULL);
            $this->redirect("/", static::REDIRECT_TEMPORARY);
        }
        
        if(!$this->db->table("ChandlerUsers")->where("id", $uuid))
            $this->flashFail("err", "Ошибка манипуляции токенами", "Пользователь не найден.");
        
        $this->assertPermission('openvk\Web\Models\Entities\User', 'substitute', 0);
        Session::i()->set("_su", $uuid);
        $this->flash("succ", "Профиль изменён", "Ваш активный профиль был изменён.");
        $this->redirect("/", static::REDIRECT_TEMPORARY);
        exit;
    }
    
    function renderLogout(): void
    {
        $this->assertUserLoggedIn();
        $this->authenticator->logout();
        Session::i()->set("_su", NULL);
        
        $this->redirect("/", static::REDIRECT_TEMPORARY_PRESISTENT);
    }
    
    function renderFinishRestoringPassword(): void
    {
        $request = $this->restores->getByToken(str_replace(" ", "+", $this->queryParam("key")));
        if(!$request || !$request->isStillValid()) {
            $this->flash("err", "Ошибка манипулирования токеном", "Токен недействителен или истёк");
            $this->redirect("/");
        }
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $user = $request->getUser()->getChandlerUser();
            $this->db->table("ChandlerTokens")->where("user", $user->getId())->delete(); #Logout from everywhere
            
            $user->updatePassword($this->postParam("password"));
            $this->authenticator->authenticate($user->getId());
            
            $request->delete(false);
            $this->flash("succ", "Успешно", "Ваш пароль был успешно сброшен.");
            $this->redirect("/settings");
        }
    }
    
    function renderRestore(): void
    {
        if(($this->queryParam("act") ?? "default") === "finish")
            $this->pass("openvk!Auth->finishRestoringPassword");
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $uRow = $this->db->table("ChandlerUsers")->where("login", $this->postParam("login"))->fetch();
            if(!$uRow) {
                #Privacy of users must be protected. We will not tell if email is bound to a user or not.
                $this->flashFail("succ", "Успешно", "Если вы зарегистрированы, вы получите инструкции на email.");
            }
            
            $user = $this->users->getByChandlerUser(new ChandlerUser($uRow));
            if(!$user)
                $this->flashFail("err", "Ошибка", "Непредвиденная ошибка при сбросе пароля.");
            
            $request = $this->restores->getLatestByUser($user);
            if(!is_null($request) && $request->isNew())
                $this->flashFail("err", "Ошибка доступа", "Нельзя делать это так часто, извините.");
            
            $resetObj = new PasswordReset;
            $resetObj->setProfile($user->getId());
            $resetObj->save();
            
            $params = [
                "key"   => $resetObj->getKey(),
                "name"  => $user->getCanonicalName(),
            ];
            $this->sendmail($uRow->login, "password-reset", $params); #Vulnerability possible
            
            
            $this->flashFail("succ", "Успешно", "Если вы зарегистрированы, вы получите инструкции на email.");
        }
    }
} 
