<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Groups extends VKAPIRequestHandler
{
    function get(int $user_id = 0, string $fields = "", int $offset = 0, int $count = 6, bool $online = false): object 
    {
        $this->requireUser();

        if($user_id == 0) {
        	foreach($this->getUser()->getClubs($offset, false, $count, true) as $club)
        		$clbs[] = $club;
        	$clbsCount = $this->getUser()->getClubCount();
        } else {
        	$users = new UsersRepo;
        	$user  = $users->get($user_id);

        	if(is_null($user))
        		$this->fail(15, "Access denied");

        	foreach($user->getClubs($offset, false, $count, true) as $club)
        		$clbs[] = $club;

        	$clbsCount = $user->getClubCount();
        }
        
        $rClubs;

        $ic = sizeof($clbs);
        if(sizeof($clbs) > $count)
            $ic = $count;

        if(!empty($clbs)) {
            for($i=0; $i < $ic; $i++) { 
                $usr = $clbs[$i];
                if(is_null($usr)) { 

                } else {
                    $rClubs[$i] = (object) [
                        "id" => $usr->getId(),
                        "name" => $usr->getName(),
                        "screen_name" => $usr->getShortCode(),
                        "is_closed" => false,
                        "can_access_closed" => true,
                    ];

                    $flds = explode(',', $fields);

                    foreach($flds as $field) { 
                        switch($field) {
                            case "verified":
                                $rClubs[$i]->verified = intval($usr->isVerified());
                                break;
                            case "has_photo":
                                $rClubs[$i]->has_photo = is_null($usr->getAvatarPhoto()) ? 0 : 1;
                                break;
                            case "photo_max_orig":
                                $rClubs[$i]->photo_max_orig = $usr->getAvatarURL();
                                break;
                            case "photo_max":
                                $rClubs[$i]->photo_max = $usr->getAvatarURL("original"); // ORIGINAL ANDREI CHINITEL 🥵🥵🥵🥵
                                break;
                            case "photo_50":
                                $rClubs[$i]->photo_50 = $usr->getAvatarURL();
                                break;
                            case "photo_100":
                                $rClubs[$i]->photo_100 = $usr->getAvatarURL("tiny");
                                break;
                            case "photo_200":
                                $rClubs[$i]->photo_200 = $usr->getAvatarURL("normal");
                                break;
                            case "photo_200_orig":
                                $rClubs[$i]->photo_200_orig = $usr->getAvatarURL("normal");
                                break;
                            case "photo_400_orig":
                                $rClubs[$i]->photo_400_orig = $usr->getAvatarURL("normal");
                                break;
                            case "members_count":
                                $rClubs[$i]->members_count = $usr->getFollowersCount();
                                break;
                        }
                    }
                }
            }
        } else {
            $rClubs = [];
        }

        return (object) [
        	"count" => $clbsCount,
        	"items" => $rClubs
        ];
    }

    function getById(string $group_ids = "", string $group_id = "", string $fields = "", int $offset = 0, int $count = 500): ?array
    {
        /* Both offset and count SHOULD be used only in OpenVK code, 
           not in your app or script, since it's not oficially documented by VK */

        $clubs = new ClubsRepo;
		
        if(empty($group_ids) && !empty($group_id)) 
            $group_ids = $group_id;
        
        if(empty($group_ids) && empty($group_id))
            $this->fail(100, "One of the parameters specified was missing or invalid: group_ids is undefined");
		
        $clbs = explode(',', $group_ids);
        $response = array();

        $ic = sizeof($clbs);

        if(sizeof($clbs) > $count)
			$ic = $count;

        $clbs = array_slice($clbs, $offset * $count);


        for($i=0; $i < $ic; $i++) {
            if($i > 500 || $clbs[$i] == 0) 
                break;

            if($clbs[$i] < 0)
                $this->fail(100, "ты ошибся чутка, у айди группы убери минус");

            $clb = $clubs->get((int) $clbs[$i]);
            if(is_null($clb)) {
                $response[$i] = (object)[
                    "id"          => intval($clbs[$i]),
                    "name"        => "DELETED",
                    "screen_name" => "club".intval($clbs[$i]),
                    "type"        => "group",
                    "description" => "This group was deleted or it doesn't exist"
                ];   
            } else if($clbs[$i] == NULL) {

            } else {
                $response[$i] = (object)[
                    "id"                => $clb->getId(),
                    "name"              => $clb->getName(),
                    "screen_name"       => $clb->getShortCode() ?? "club".$clb->getId(),
                    "is_closed"         => false,
                    "type"              => "group",
                    "is_member"         => !is_null($this->getUser()) ? (int) $clb->getSubscriptionStatus($this->getUser()) : 0,
                    "can_access_closed" => true,
                ];

                $flds = explode(',', $fields);

                foreach($flds as $field) { 
                    switch($field) {
			            case "verified":
			                $response[$i]->verified = intval($clb->isVerified());
			                break;
			            case "has_photo":
			                $response[$i]->has_photo = is_null($clb->getAvatarPhoto()) ? 0 : 1;
			                break;
			            case "photo_max_orig":
			                $response[$i]->photo_max_orig = $clb->getAvatarURL();
			                break;
			            case "photo_max":
			                $response[$i]->photo_max = $clb->getAvatarURL();
			                break;
                        case "photo_50":
                            $response[$i]->photo_50 = $clb->getAvatarURL();
                            break;
                        case "photo_100":
                            $response[$i]->photo_100 = $clb->getAvatarURL("tiny");
                            break;
                        case "photo_200":
                            $response[$i]->photo_200 = $clb->getAvatarURL("normal");
                            break;
                        case "photo_200_orig":
                            $response[$i]->photo_200_orig = $clb->getAvatarURL("normal");
                            break;
                        case "photo_400_orig":
                            $response[$i]->photo_400_orig = $clb->getAvatarURL("normal");
                            break;
			            case "members_count":
			                $response[$i]->members_count = $clb->getFollowersCount();
			                break;
			            case "site":
			                $response[$i]->site = $clb->getWebsite();
			                break;
                        case "description":
			                $response[$i]->description = $clb->getDescription();
                            break;
			            case "contacts":
                            $contacts;
                            $contactTmp = $clb->getManagers(1, true);

                            foreach($contactTmp as $contact)
                                $contacts[] = array(
                                    "user_id" => $contact->getUser()->getId(),
                                    "desc"    => $contact->getComment()
                                );

			                $response[$i]->contacts = $contacts;
			                break;
                        case "can_post":
                            if(!is_null($this->getUser()))
                                if($clb->canBeModifiedBy($this->getUser()))
                                    $response[$i]->can_post = true;
                                else
                                    $response[$i]->can_post = $clb->canPost();
                            break;
                    }
                }
            }
        }

        return $response;
    }

    function search(string $q, int $offset = 0, int $count = 100)
    {
        $clubs = new ClubsRepo;
        
        $array = [];
		$find  = $clubs->find($q);

        foreach ($find as $group)
            $array[] = $group->getId();

        return (object) [
        	"count" => $find->size(),
        	"items" => $this->getById(implode(',', $array), "", "is_admin,is_member,is_advertiser,photo_50,photo_100,photo_200", $offset, $count)
            /*
             * As there is no thing as "fields" by the original documentation
             * i'll just bake this param by the example shown here: https://dev.vk.com/method/groups.search 
             */
        ];
    }

    function join(int $group_id)
    {
        $this->requireUser();
        
        $club = (new ClubsRepo)->get($group_id);
        
        $isMember = !is_null($this->getUser()) ? (int) $club->getSubscriptionStatus($this->getUser()) : 0;

        if($isMember == 0)
            $club->toggleSubscription($this->getUser());

        return 1;
    }

    function leave(int $group_id)
    {
        $this->requireUser();
        
        $club = (new ClubsRepo)->get($group_id);
        
        $isMember = !is_null($this->getUser()) ? (int) $club->getSubscriptionStatus($this->getUser()) : 0;

        if($isMember == 1)
            $club->toggleSubscription($this->getUser());

        return 1;
    }
}
