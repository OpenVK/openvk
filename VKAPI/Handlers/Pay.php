<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Applications;

final class Pay extends VKAPIRequestHandler
{
    public function getIdByMarketingId(string $marketing_id): int
    {
        [$hexId, $signature] = explode("_", $marketing_id);
        try {
            $key = CHANDLER_ROOT_CONF["security"]["secret"];
            if (sodium_memcmp(base64_decode($signature), hash_hmac("sha512/224", $hexId, $key, true)) == -1) {
                $this->fail(4, "Invalid marketing id");
            }
        } catch (\SodiumException $e) {
            $this->fail(4, "Invalid marketing id");
        }

        return hexdec($hexId);
    }

    public function verifyOrder(int $app_id, float $amount, string $signature): bool
    {
        $this->requireUser();

        $app = (new Applications())->get($app_id);
        if (!$app) {
            $this->fail(26, "No app found with this id");
        } elseif ($app->getOwner()->getId() != $this->getUser()->getId()) {
            $this->fail(15, "Access error");
        }

        [$time, $signature] = explode(",", $signature);
        try {
            $key = CHANDLER_ROOT_CONF["security"]["secret"];
            if (sodium_memcmp($signature, hash_hmac("whirlpool", "$app_id:$amount:$time", $key)) == -1) {
                $this->fail(4, "Invalid order");
            }
        } catch (\SodiumException $e) {
            $this->fail(4, "Invalid order");
        }

        return true;
    }
}
