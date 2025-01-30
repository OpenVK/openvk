<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\PasswordReset;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Restores
{
    private $context;
    private $restores;

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->restores = $this->context->table("password_resets");
    }

    public function toPasswordReset(?ActiveRow $ar): ?PasswordReset
    {
        return is_null($ar) ? null : new PasswordReset($ar);
    }

    public function getByToken(string $token): ?PasswordReset
    {
        return $this->toPasswordReset($this->restores->where("key", $token)->fetch());
    }

    public function getLatestByUser(User $user): ?PasswordReset
    {
        return $this->toPasswordReset($this->restores->where("profile", $user->getId())->order("timestamp DESC")->fetch());
    }
}
