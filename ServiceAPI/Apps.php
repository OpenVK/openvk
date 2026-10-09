<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use Nette\Database\UniqueConstraintViolationException;
use openvk\Web\Models\Entities\APIToken;
use openvk\Web\Models\Entities\Application;
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
        $this->apps = new Applications();
    }

    public function getUserInfo(callable $resolve, callable $reject): void
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

    public function updatePermission(int $app, string $perm, string $state, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($app);
        if (!$app || !$app->isEnabled()) {
            $reject(15, "No application with this id found");
            return;
        }

        if (!$app->setPermission($this->user, $perm, $state == "yes")) {
            $reject(100, "Invalid permission $perm");
        }

        $resolve(1);
    }

    public function pay(int $appId, float $amount, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($appId);
        if (!$app || !$app->isEnabled()) {
            $reject(15, "No application with this id found");
            return;
        }

        if ($amount < 0 || !is_finite($amount)) {
            $reject(552, "Payment amount is invalid");
            return;
        }

        if (!$this->transfer($app, $amount, null)) {
            $reject(41, "Not enough money");
            return;
        }

        $t = time();
        $resolve($t . "," . hash_hmac("whirlpool", "$appId:$amount:$t", CHANDLER_ROOT_CONF["security"]["secret"]));
    }

    public function payOrder(int $appId, float $amount, string $orderId, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($appId);
        if (!$app || !$app->isEnabled()) {
            $reject(15, "No application with this id found");
            return;
        }

        if ($amount <= 0 || !is_finite($amount) || round($amount, 6) != $amount) {
            $reject(552, "Payment amount is invalid");
            return;
        }

        if (!preg_match("/^[A-Za-z0-9._-]{1,64}\z/", $orderId)) { # \z: $ would allow a trailing newline
            $reject(553, "Order ID is invalid");
            return;
        }

        $duplicate = false;
        try {
            $payment = $this->transfer($app, $amount, $orderId);
        } catch (UniqueConstraintViolationException $ex) {
            # order was already paid: return the same receipt instead of charging again
            $duplicate = true;
            $payment   = DatabaseConnection::i()->getContext()->table("app_payments")->where([
                "app"      => $appId,
                "order_id" => $orderId,
            ])->fetch();

            if ((int) $payment->user !== $this->user->getId() || (float) $payment->amount !== $amount) {
                $reject(554, "Order conflict: this order was paid by another user or with another amount");
                return;
            }
        }

        if (!$payment) {
            $reject(41, "Not enough money");
            return;
        }

        $resolve([
            "duplicate" => $duplicate,
            "receipt"   => $app->signParams([
                "ovk_type"       => "payment",
                "ovk_app_id"     => (string) $appId,
                "ovk_user_id"    => (string) $payment->user,
                "ovk_order_id"   => $payment->order_id,
                "ovk_payment_id" => (string) $payment->id,
                "ovk_amount"     => rtrim(rtrim(number_format((float) $payment->amount, 6, ".", ""), "0"), "."),
                "ovk_ts"         => (string) $payment->created,
            ]),
        ]);
    }

    /**
     * Moves coins from the user to the app and logs the payment, all in one transaction.
     * Returns null if the user doesn't have enough coins.
     */
    private function transfer(Application $app, float $amount, ?string $orderId): ?ActiveRow
    {
        # same as User::getCoins(), which is always 0 with commerce disabled
        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"] && $amount > 0) {
            return null;
        }

        $db = DatabaseConnection::i()->getContext();
        $db->beginTransaction();
        try {
            # goes first: unique (app, order_id) makes concurrent payments for the same order wait here
            $payment = $db->table("app_payments")->insert([
                "app"      => $app->getId(),
                "user"     => $this->user->getId(),
                "order_id" => $orderId,
                "amount"   => $amount,
                "created"  => time(),
            ]);

            $coins = $db->query("SELECT coins FROM profiles WHERE id = ? FOR UPDATE", $this->user->getId())->fetchField();
            if ($coins < $amount) {
                $db->rollBack();
                return null;
            }

            $db->query("UPDATE profiles SET coins = coins - ? WHERE id = ?", $amount, $this->user->getId());
            $db->query("UPDATE apps SET coins = coins + ? WHERE id = ?", $amount, $app->getId());
            $db->commit();
        } catch (\Throwable $ex) {
            $db->rollBack();
            throw $ex;
        }

        return $payment;
    }

    public function withdrawFunds(int $appId, callable $resolve, callable $reject): void
    {
        $app = $this->apps->get($appId);
        if (!$app) {
            $reject(15, "No application with this id found");
            return;
        } elseif ($app->getOwner()->getId() != $this->user->getId()) {
            $reject(15, "You don't have rights to edit this app");
            return;
        }

        $resolve($app->withdrawCoins());
    }

    public function getRegularToken(string $clientName, bool $acceptsStale, callable $resolve, callable $reject): void
    {
        $token = null;
        $stale = true;
        if ($acceptsStale) {
            $token = (new APITokens())->getStaleByUser($this->user->getId(), $clientName);
        }

        if (is_null($token)) {
            $stale = false;
            $token = new APIToken();
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
