<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Clubs;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\Postable;
use openvk\Web\Models\Repositories\Posts as PostsRepo;

final class Groups extends VKAPIRequestHandler
{
    function get(string $group_ids, string $fields = "", int $offset = 0, int $count = 100, bool $online = false): array 
    {
        $this->requireUser();

        $clubs = new ClubsRepo;
        $clbs = explode(',', $group_ids);
        $response;

        $ic = sizeof($clbs);

        if(sizeof($clbs) > $count) $ic = $count;

        $clbs = array_slice($clbs, $offset * $count);

        for ($i=0; $i < $ic; $i++) { 
            $usr = $clubs->get((int) $clbs[$i]);
            if(is_null($usr))
            {
                $response[$i] = (object)[
                    "id" => $clbs[$i],
                    "first_name" => "DELETED",
                    "last_name" => "",
                    "deactivated" => "deleted"
                ];   
            }else if($clbs[$i] == null){

            }else{
                $response[$i] = (object)[
                    "id" => $usr->getId(),
                    "first_name" => $usr->getFirstName(),
                    "last_name" => $usr->getLastName(),
                    "is_closed" => false,
                    "can_access_closed" => true,
                ];

                $flds = explode(',', $fields);

                foreach($flds as $field) { 
                    switch ($field) {
                        case 'verified':
                            $response[$i]->verified = intval($usr->isVerified());
                            break;
                        case 'sex':
                            $response[$i]->sex = $this->getUser()->isFemale() ? 1 : 2;
                            break;
                        case 'has_photo':
                            $response[$i]->has_photo = is_null($usr->getAvatarPhoto()) ? 0 : 1;
                            break;
                        case 'photo_max_orig':
                            $response[$i]->photo_max_orig = $usr->getAvatarURL();
                            break;
                        case 'photo_max':
                            $response[$i]->photo_max = $usr->getAvatarURL();
                            break;

                    }
                }

		// НУЖЕН фикс - либо из-за моего дебилизма, либо из-за сегментации котлеток некоторые пользовали отображаются как онлайн, хотя лол, если зайти на страницу, то оный уже офлайн
		if($online == true && $usr->getOnline()->timestamp() + 2505600 > time()) {
		    $response[$i]->online = 1;
		}else{
		    $response[$i]->online = 0;
		}

            }
        }

        return $response;
    }
}
