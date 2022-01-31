<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\EmailVerification;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Verifications
{
    private $context;
    private $verifications;
    
    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->verifications = $this->context->table("email_verifications");
    }

    function toEmailVerification(?ActiveRow $ar): ?EmailVerification
    {
        return is_null($ar) ? NULL : new EmailVerification($ar);
    }
    
    function getByToken(string $token): ?EmailVerification
    {
        return $this->toEmailVerification($this->verifications->where("key", $token)->fetch());
    }
    
    function getLatestByUser(User $user): ?EmailVerification
    {
        return $this->toEmailVerification($this->verifications->where("profile", $user->getId())->order("timestamp DESC")->fetch());
    }
}
