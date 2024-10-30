<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\User;

trait TIgnorable 
{
    function isIgnoredBy(User $user): bool
    {
        $ctx  = DatabaseConnection::i()->getContext();
        $data = [
            "owner"  => $user->getId(),
            "source" => $this->getRealId(),
        ];

        $sub = $ctx->table("ignored_sources")->where($data);
        return $sub->count() > 0;
    }

    function addIgnore(User $for_user): bool
    {
        DatabaseConnection::i()->getContext()->table("ignored_sources")->insert([
            "owner"  => $for_user->getId(),
            "source" => $this->getRealId(),
        ]);
        
        return true;
    }

    function removeIgnore(User $for_user): bool
    {
        DatabaseConnection::i()->getContext()->table("ignored_sources")->where([
            "owner"  => $for_user->getId(),
            "source" => $this->getRealId(),
        ])->delete();
        
        return true;
    }

    function toggleIgnore(User $for_user): bool
    {
        if($this->isIgnoredBy($for_user)) {
            $this->removeIgnore($for_user);

            return false;
        } else {
            $this->addIgnore($for_user);

            return true;
        }
    }
}
