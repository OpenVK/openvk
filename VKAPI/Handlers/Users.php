<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Users extends VKAPIRequestHandler
{
    function get(string $user_ids, string $fields = "", int $offset = 0, int $count = 100): array 
    {
        $this->requireUser();

        $users = new UsersRepo;
        $usrs = explode(',', $user_ids);
        $response;

        $ic = sizeof($usrs);

        if(sizeof($usrs) > $count) $ic = $count;

        $usrs = array_slice($usrs, $offset * $count);

        for ($i=0; $i < $ic; $i++) { 
            $usr = $users->get((int) $usrs[$i]);
            if(is_null($usr))
            {
                $response[$i] = (object)[
                    "id" => $usrs[$i],
                    "first_name" => "DELETED",
                    "last_name" => "",
                    "deactivated" => "deleted"
                ];   
            }else if($usrs[$i] == null){

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
			case 'status':
			    $response[$i]->status = $usr->getStatus();
			    break;
			case 'screen_name':
                            $response[$i]->screen_name = $usr->getShortCode();
                            break;
                        case 'music':
                            $response[$i]->music = $usr->getFavoriteMusic();
                            break;
                        case 'movies':
                            $response[$i]->movies = $usr->getFavoriteFilms();
                            break;
                        case 'tv':
                            $response[$i]->tv = $usr->getFavoriteShows();
                            break;
                        case 'books':
                            $response[$i]->books = $usr->getFavoriteBooks();
                            break;
                        case 'city':
                            $response[$i]->city = $usr->getCity();
                            break;
                        case 'interests':
                            $response[$i]->interests = $usr->getInterests();
                            break;	    
                    }
                }

		// НУЖЕН фикс - либо из-за моего дебилизма, либо из-за сегментации котлеток некоторые пользовали отображаются как онлайн, хотя лол, если зайти на страницу, то оный уже офлайн
		if($usr->getOnline()->timestamp() + 2505600 > time()) {
		    $response[$i]->online = 1;
		}else{
		    $response[$i]->online = 0;
		}

            }
        }

        return $response;
    }

    /* private function getUsersById(string $user_ids, string $fields = "", int $offset = 0, int $count = PHP_INT_MAX){

    } */

    function getFollowers(int $user_id, string $fields = "", int $offset = 0, int $count = 100): object
    {
        $offset++;
        $followers = [];

        $users = new UsersRepo;

        $this->requireUser();
        
        foreach ($users->get($user_id)->getFollowers($offset, $count) as $follower) {
            $followers[] = $follower->getId();
        }

        $response = $followers;

        if (!is_null($fields)) {
        	$response = $this->get(implode(',', $followers), $fields, 0, $count);
        }

        return (object) [
            "count" => $users->get($user_id)->getFollowersCount(),
            "items" => $response
        ];
    }

    function search(string $q, string $fields = '', int $offset = 0, int $count = 100)
    {
        $users = new UsersRepo;
        
        $array = [];

        foreach ($users->find($q) as $user) {
            $array[] = $user->getId();
        }

        return (object)[
        	"count" => $users->getFoundCount($q),
        	"items" => $this->get(implode(',', $array), $fields, $offset, $count)
        ];
    }
}
