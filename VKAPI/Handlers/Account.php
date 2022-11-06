<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

final class Account extends VKAPIRequestHandler
{
    function getProfileInfo(): object
    {
        $this->requireUser();

        return (object) [
            "first_name"       => $this->getUser()->getFirstName(),
            "id"               => $this->getUser()->getId(),
            "last_name"        => $this->getUser()->getLastName(),
            "home_town"        => $this->getUser()->getHometown(),
            "status"           => $this->getUser()->getStatus(),
            "bdate"            => is_null($this->getUser()->getBirthday()) ? '01.01.1970' : $this->getUser()->getBirthday()->format('%e.%m.%Y'),
            "bdate_visibility" => $this->getUser()->getBirthdayPrivacy(),
            "phone"            => "+420 ** *** 228",                       # TODO
            "relation"         => $this->getUser()->getMaritalStatus(),
            "sex"              => $this->getUser()->isFemale() ? 1 : 2
        ];
    }

    function getInfo(): object
    {
        $this->requireUser();

        return (object) [
            "2fa_required"                  => $this->getUser()->is2faEnabled() ? 1 : 0,
            "country"                       => "CZ",                                  # TODO
            "eu_user"                       => false,                                 # TODO
            "https_required"                => 1,
            "intro"                         => 0,
            "community_comments"            => false,
            "is_live_streaming_enabled"     => false,
            "is_new_live_streaming_enabled" => false,
            "lang"                          => 1,
            "no_wall_replies"               => 0,
            "own_posts_default"             => 0
        ];
    }

    function setOnline(): int
    {
        $this->requireUser();

        $this->getUser()->setOnline(time());
        $this->getUser()->save();
        
        return 1;
    }

    function setOffline(): object
    {
        $this->requireUser();

        # Цiй метод є заглушка
 
        return 1;
    }

    function getAppPermissions(): int
    {
        return 9355263;
    }

    function getCounters(string $filter = ""): object
    {
        return (object) [
            "friends"       => $this->getUser()->getFollowersCount(),
            "notifications" => $this->getUser()->getNotificationsCount(),
            "messages"      => $this->getUser()->getUnreadMessagesCount()
        ];

        # TODO: Filter
    }

    function saveProfileInfo(string $first_name = "", string $last_name = "", string $screen_name = "", int $sex = -1, int $relation = -1, string $bdate = "", int $bdate_visibility = -1, string $home_town = "", string $status = ""): object {
        $this->requireUser();

        $output = [
            "changed" => 0,
        ];

        if ($first_name != "" || $last_name != "") {
            $output["name_request"] = [
                "id" => 1, # Заглушка
                "status" => "success",
                "first_name" => ($first_name != "") ? $first_name : $this->getUser()->getFirstName(),
                "last_name" => ($last_name != "") ? $last_name : $this->getUser()->getLastName(),
            ];
        }

        if ($first_name != "") {
            try {
                $this->getUser()->setFirst_name($first_name);
            } catch(\Exception $e) {
                $output["name_request"]["status"] = "declined";
            }
        }

        if ($last_name != "") {
            try {
                $this->getUser()->setLast_Name($last_name);
            } catch(\Exception $e) {
                $output["name_request"]["status"] = "declined";
            }
        }

        if ($screen_name != "") {
            if (!$this->getUser()->setShortCode($screen_name)) {
                $this->fail(1260, "Invalid screen name");
            }
        }

        if ($sex > -1) {
            if ($sex == 1) 
                $this->getUser()->setSex(0);
            if ($sex == 2)
                $this->getUser()->setSex(1);
        }
            
        if ($relation > -1)
            $this->getUser()->setMarital_Status($relation);

        if ($bdate != "")
            $this->getUser()->setBirthday(strtotime($bdate));

        if ($bdate_visibility >= 1) {
            if ($bdate_visibility == 2)
                $this->getUser()->setBirthday_privacy(1);
            if ($bdate_visibility == 1)
                $this->getUser()->setBirthday_privacy(0);
        }
        
        if ($home_town != "")
            $this->getUser()->setHometown($home_town);

        if ($status != "") 
            $this->getUser()->setStatus($status);        
        
        if ($first_name != "" || $last_name != "" || $screen_name != "" || $sex > 0 || $relation > -1 || $bdate != "" || $bdate_visibility != "" || $home_town != "" || $status != "") {
            $output["changed"] = 1;
            $this->getUser()->save();
        }

        return (object) $output;
    }
}
