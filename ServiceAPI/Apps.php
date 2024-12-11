<?php declare(strict_types=1);
namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\APIToken;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\APITokens;
use openvk\Web\Models\Repositories\Applications;
use WhichBrowser;

class Apps implements Handler
{
    private $user;
    private $apps;
    
    public function __construct(?User $user)
    {
        $this->user = $user;
        $this->apps = new Applications;
    }
    
    function getUserInfo(callable $resolve, callable $reject): void
    {
        $hexId       = dechex($this->user->getId());
        $sign        = hash_hmac("sha512/224", $hexId, CHANDLER_ROOT_CONF["security"]["secret"], true);
        $marketingId = $hexId . "_" . base64_encode($sign);
        
        $resolve([
            "id"           => $this->user->getId(),
            "marketing_id" => $marketingId,
            "name"         => [
                "first" => $this->user->getFirstName(),
                "last"  => $this->user->getLastName(),
                "full"  => $this->user->getFullName(),
            ],
            "ava" => $this->user->getAvatarUrl(),
        ]);
    }
    
    function updatePermission(int $app, string $perm, string $state, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($app);
        if(!$app || !$app->isEnabled()) {
            $reject("No application with this id found");
            return;
        }
        
        if(!$app->setPermission($this->user, $perm, $state == "yes"))
            $reject("Invalid permission $perm");
        
        $resolve(1);
    }
    
    function pay(int $appId, float $amount, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($appId);
        if(!$app || !$app->isEnabled()) {
            $reject("No application with this id found");
            return;
        }

        if($amount < 0) {
            $reject(552, "Payment amount is invalid");
            return;
        }
        
        $coinsLeft = $this->user->getCoins() - $amount;
        if($coinsLeft < 0) {
            $reject(41, "Not enough money");
            return;
        }
        
        $this->user->setCoins($coinsLeft);
        $this->user->save();
        $app->addCoins($amount);
        
        $t = time();
        $resolve($t . "," . hash_hmac("whirlpool", "$appId:$amount:$t", CHANDLER_ROOT_CONF["security"]["secret"]));
    }
    
    function withdrawFunds(int $appId, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($appId);
        if(!$app) {
            $reject("No application with this id found");
            return;
        } else if($app->getOwner()->getId() != $this->user->getId()) {
            $reject("You don't have rights to edit this app");
            return;
        }
        
        $coins = $app->getBalance();
        $app->withdrawCoins();
        $resolve($coins);
    }
    
    function getRegularToken(string $clientName, bool $acceptsStale, callable $resolve, callable $reject): void
    {
        $token = NULL;
        $stale = true;
        if($acceptsStale)
            $token = (new APITokens)->getStaleByUser($this->user->getId(), $clientName);
        
        if(is_null($token)) {
            $stale = false;
            $token = new APIToken;
            $token->setUser($this->user);
            $token->setPlatform($clientName ?? (new WhichBrowser\Parser(getallheaders()))->toString());
            $token->save();
        }
        
        $resolve([
            'is_stale' => $stale,
            'token'    => $token->getFormattedToken(),
        ]);
    }
}