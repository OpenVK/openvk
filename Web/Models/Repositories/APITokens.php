<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\APIToken;

class APITokens extends Repository
{
    protected $tableName = "api_tokens";
    protected $modelName = "APIToken";

    public function getByCode(string $code, bool $withRevoked = false): ?APIToken
    {
        $parts  = explode("-", $code);
        $id     = $parts[0];
        $secret = implode("", array_slice($parts, 1, 9));

        $token = $this->get((int) $id);
        if (!$token) {
            return null;
        } elseif ($token->getSecret() !== $secret) {
            return null;
        } elseif ($token->isRevoked() && !$withRevoked) {
            return null;
        }

        return $token;
    }

    public function getStaleByUser(int $userId, string $platform, bool $withRevoked = false): ?APIToken
    {
        return $this->toEntity($this->table->where([
            'user'     => $userId,
            'platform' => $platform,
            'deleted'  => $withRevoked,
        ])->fetch());
    }
}
