<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection as DB;
use Chandler\Security\Authenticator;
use openvk\VKAPI\ClientRegistry;
use openvk\Web\Models\Entities\{User, APIToken};
use openvk\Web\Models\Repositories\{Users, APITokens};
use lfkeitel\phptotp\{Base32, Totp};

final class Auth extends VKAPIRequestHandler
{
    public function validateAccount(): object
    {
        // dummy function, always return passwd
        return (object) [
            "flow_name" => "need_password",
            "sid" => "1",
        ];
    }

    public function getTokenSecure(?string $nonce = null, ?int $api_id = null): array
    {
        return [
            "token" => bin2hex(random_bytes(16)),
        ];
    }

    public function getSessionSecure(
        ?string $login = null,
        ?string $username = null,
        ?string $password = null,
        ?string $digest = null,
        ?int $api_id = null,
        ?string $nonce = null,
        mixed $client_id = null,
        ?string $code = null
    ): array {
        $login = !empty($login) ? trim($login) : (!empty($username) ? trim($username) : null);
        $password = !empty($password) ? $password : $digest;

        if (empty($login) || empty($password)) {
            $this->fail(100, "Password and login not passed");
        }

        $chUser = DB::i()->getContext()->table("ChandlerUsers")->where("login", $login)->fetch();
        if (!$chUser) {
            $profile = DB::i()->getContext()->table("profiles")->where("email", $login)->fetch();
            if ($profile && $profile->user) {
                $chUser = DB::i()->getContext()->table("ChandlerUsers")->where("id", $profile->user)->fetch();
            }
        }

        if (!$chUser) {
            $this->fail(28, "Invalid login or password");
        }

        $auth = Authenticator::i();
        if (!$auth->verifyCredentials($chUser->id, $password)) {
            $this->fail(28, "Invalid login or password");
        }

        $uId  = $chUser->related("profiles.user")->fetch()->id;
        $user = (new Users())->get($uId);

        if (!$user) {
            $this->fail(28, "Invalid login or password");
        }

        if (!$user->isActivated() && (OPENVK_ROOT_CONF['openvk']['preferences']['security']['requireEmail'] ?? false) === true) {
            $this->fail(7, "Access denied");
        }

        if ($user->isBanned() || $user->isDeleted()) {
            $this->fail(18, "User was deleted or banned");
        }

        if ($user->is2faEnabled()) {
            if (empty($code) || !($code === (new Totp())->GenerateToken(Base32::decode($user->get2faSecret())) || $user->use2faBackupCode((int) $code))) {
                $this->fail(28, "Invalid 2FA code");
            }
        }

        $rawClientId = !empty($api_id) ? $api_id : $client_id;
        $clientInfo  = ClientRegistry::resolve($rawClientId);
        $platform    = $clientInfo['tag'] ?? null;
        $clientId    = !empty($rawClientId) && is_numeric($rawClientId) ? (int) $rawClientId : ($clientInfo['id'] ?? null);

        if (empty($platform)) {
            $platform = "vk_android";
        }

        $token = (new APITokens())->getStaleByUser($uId, $platform);
        if (is_null($token)) {
            $token = new APIToken();
            $token->setUser($user);
            if (!empty($clientId)) {
                $token->setClientId((int) $clientId);
            }
            $token->setPlatform($platform);
            $token->save();
        }

        return [
            "auth"   => "success",
            "id"     => $uId,
            "sid"    => $token->getFormattedToken(),
            "secret" => $token->getSecret(),
        ];
    }
}