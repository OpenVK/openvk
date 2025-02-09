<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\EmailVerification;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Verifications
{
    private $context;
    private $verifications;

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->verifications = $this->context->table("email_verifications");
    }

    public function toEmailVerification(?ActiveRow $ar): ?EmailVerification
    {
        return is_null($ar) ? null : new EmailVerification($ar);
    }

    public function getByToken(string $token): ?EmailVerification
    {
        return $this->toEmailVerification($this->verifications->where("key", $token)->fetch());
    }

    public function getLatestByUser(User $user): ?EmailVerification
    {
        return $this->toEmailVerification($this->verifications->where("profile", $user->getId())->order("timestamp DESC")->fetch());
    }
}
