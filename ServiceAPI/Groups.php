<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Clubs;

class Groups implements Handler
{
    protected $user;
    protected $groups;
    
    function __construct(?User $user)
    {
        $this->user  = $user;
        $this->groups = new Clubs;
    }
    
    function getWriteableClubs(callable $resolve, callable $reject)
    {
        $clubs  = [];
        $wclubs = $this->groups->getWriteableClubs($this->user->getId());

        if(count(iterator_to_array($this->groups->getWriteableClubs($this->user->getId()))) == 0) {
            $reject("You did not created any groups");

            return;
        }

        foreach($wclubs as $club) {
            $clubs[] = [
                "name"   => $club->getName(),
                "id"     => $club->getId(),
                "avatar" => $club->getAvatarUrl() # если в овк когда-нибудь появится крутой список с аватарками, то можно использовать это поле
            ];
        }

        $resolve($clubs);
    }
}
