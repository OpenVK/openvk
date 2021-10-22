<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Clubs;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\Postable;
use openvk\Web\Models\Repositories\Posts as PostsRepo;

final class Groups extends VKAPIRequestHandler
{
    function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 6, bool $online = false): object 
    {
        $this->requireUser();

        if ($user_id == 0) {
        	foreach($this->getUser()->getClubs($offset+1) as $club) {
        		$clbs[] = $club;
        	}
        	$clbsCount = $this->getUser()->getClubCount();
        } else {
        	$users = new UsersRepo;
        	$user = $users->get($user_id);
        	if (is_null($user)) {
        		$this->fail(15, "Access denied");
        	}
        	foreach($user->getClubs($offset+1) as $club) {
        		$clbs[] = $club;
        	}
        	$clbsCount = $user->getClubCount();
        }
        
        $rClubs;

        $ic = sizeof($clbs);

        if(sizeof($clbs) > $count) $ic = $count;

        $clbs = array_slice($clbs, $offset * $count);

        for ($i=0; $i < $ic; $i++) { 
            $usr = $clbs[$i];
            if(is_null($usr))
            {
                $rClubs[$i] = (object)[
                    "id" => $clbs[$i],
                    "name" => "DELETED",
                    "deactivated" => "deleted"
                ];   
            }else if($clbs[$i] == null){

            }else{
                $rClubs[$i] = (object)[
                    "id" => $usr->getId(),
                    "name" => $usr->getName(),
                    "screen_name" => $usr->getShortCode(),
                    "is_closed" => false,
                    "can_access_closed" => true,
                ];

                $flds = explode(',', $fields);

                foreach($flds as $field) { 
                    switch ($field) {
                        case 'verified':
                            $rClubs[$i]->verified = intval($usr->isVerified());
                            break;
                        case 'has_photo':
                            $rClubs[$i]->has_photo = is_null($usr->getAvatarPhoto()) ? 0 : 1;
                            break;
                        case 'photo_max_orig':
                            $rClubs[$i]->photo_max_orig = $usr->getAvatarURL();
                            break;
                        case 'photo_max':
                            $rClubs[$i]->photo_max = $usr->getAvatarURL();
                            break;
						case 'members_count':
							$rClubs[$i]->members_count = $usr->getFollowersCount();
							break;
                    }
                }
            }
        }

        return (object) [
        	"count" => $clbsCount,
        	"items" => $rClubs
        ];
    }
}
