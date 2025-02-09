<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\EmailChangeVerification;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class EmailChangeVerifications
{
    private $context;
    private $verifications;

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->verifications = $this->context->table("email_change_verifications");
    }

    public function toEmailChangeVerification(?ActiveRow $ar): ?EmailChangeVerification
    {
        return is_null($ar) ? null : new EmailChangeVerification($ar);
    }

    public function getByToken(string $token): ?EmailChangeVerification
    {
        return $this->toEmailChangeVerification($this->verifications->where("key", $token)->fetch());
    }

    public function getLatestByUser(User $user): ?EmailChangeVerification
    {
        return $this->toEmailChangeVerification($this->verifications->where("profile", $user->getId())->order("timestamp DESC")->fetch());
    }
}
