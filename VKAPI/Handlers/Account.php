<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

final class Account extends VKAPIRequestHandler
{
    function getProfileInfo(): object
    {
        $this->requireUser();

        return (object) [
            "first_name" => $this->getUser()->getFirstName(),
            "id" => $this->getUser()->getId(),
            "last_name" => $this->getUser()->getLastName(),
            "home_town" => $this->getUser()->getHometown(),
            "status" => $this->getUser()->getStatus(),
            "bdate" => "1.1.1970",                              // TODO
            "bdate_visibility" => 0,                            // TODO
            "phone" => "+420 ** *** 228",                       // TODO
            "relation" => $this->getUser()->getMaritalStatus(),
            "sex" => $this->getUser()->isFemale() ? 1 : 2
        ];
    }

    function getInfo(): object
    {
        $this->requireUser();

        // Цiй метод є заглушка

        return (object) [
            "2fa_required" => 0,
            "country" => "CZ",                                  // TODO
            "eu_user" => false,                                 // TODO
            "https_required" => 1,
            "intro" => 0,
            "community_comments" => false,
            "is_live_streaming_enabled" => false,
            "is_new_live_streaming_enabled" => false,
            "lang" => 1,
            "no_wall_replies" => 0,
            "own_posts_default" => 0
        ];
    }

    function setOnline(): object
    {
        $this->requireUser();

        $this->getUser()->setOnline(time());

        return 1;
    }

    function setOffline(): object
    {
        $this->requireUser();

        // Цiй метод є заглушка
 
        return 1;
    }

    function getAppPermissions(): object
    {
        return 9355263;
    }

    function getCounters(string $filter = ""): object
    {
        return (object) [
            "friends" => $this->getUser()->getFollowersCount(),
            "notifications" => $this->getUser()->getNotificationsCount()
        ];

        // TODO: Filter
    }
    
    function saveProfileInfo(?string $First_name, ?string $Last_name, ?int $cancel_request_id = 0, ?int $sex = 0, ?int $relation = 0, ?string $status = "", ?string $screen_name = ""): object
    {
        $this->requireUser();

        $user = $this->getUser();

        $answer = (object) [ "changed" => 1 ];

        if ($First_name != "")
        {
            $user->setfirst_name($First_name);
            $answer->name_request = [
                "status" => "success",
                "first_name" => $First_name,
                "last_name" => $this->getUser()->getLastName()
            ];
        }

        if ($Last_name != "")
        {
            $user->setlast_name($Last_name);
            $answer->name_request = [
                "status" => "success",
                "first_name" => $First_name,
                "last_name" => $Last_name
            ];
        }

        if ($sex != 0)
            $user->setsex($sex);

        if ($relation != 0)
            $user->setrelation($relation);

        if ($status != "")
            $user->setstatus($status);

        if ($screen_name != "")
            $user->setShortCode($screen_name);

        if ($First_name == "" && $Last_name == "" && $sex == 0 && $status == "" && $relation == 0 && $screen_name == "")
        {
            $answer->changed = 0;
            return $answer;
        }

        $user->save();

        return $answer;
    }
}
