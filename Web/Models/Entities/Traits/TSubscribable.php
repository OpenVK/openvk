<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection;

trait TSubscribable
{
    /*function getSubscribers(): \Traversable
    {
        $subs = DatabaseConnection::i()->getContext()->table("subscriptions")->where([
            "model"  => static::class,
            "target" => $this->getId(),
        ]);
        
        foreach($subs as $sub) {
            $sub = (new Users)->get($sub->follower);
            if(!$sub) continue;
            
            yield $sub;
        }
    }*/
    
    function toggleSubscription(User $user): bool
    {
        $ctx  = DatabaseConnection::i()->getContext();
        $data = [
            "follower" => $user->getId(),
            "model"    => static::class,
            "target"   => $this->getId(),
        ];
        $sub  = $ctx->table("subscriptions")->where($data);
        
        if(!($sub->fetch())) {
            $ctx->table("subscriptions")->insert($data);
            return true;
        }
        
        $sub->delete();
        return false;
    }
}
