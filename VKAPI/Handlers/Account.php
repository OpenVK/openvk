<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Exceptions\InvalidUserNameException;

final class Account extends VKAPIRequestHandler
{
    function getProfileInfo(): object
    {
        $this->requireUser();
        $user = $this->getUser();
        $return_object = (object) [
            "first_name"       => $user->getFirstName(),
            "photo_200"        => $user->getAvatarURL("normal"),
            "nickname"         => $user->getPseudo(),
            "is_service_account" => false,
            "id"               => $user->getId(),
            "is_verified"      => $user->isVerified(),
            "verification_status" => $user->isVerified() ? 'verified' : 'unverified',
            "last_name"        => $user->getLastName(),
            "home_town"        => $user->getHometown(),
            "status"           => $user->getStatus(),
            "bdate"            => is_null($user->getBirthday()) ? '01.01.1970' : $user->getBirthday()->format('%e.%m.%Y'),
            "bdate_visibility" => $user->getBirthdayPrivacy(),
            "phone"            => "+420 ** *** 228",                       # TODO
            "relation"         => $user->getMaritalStatus(),
            "screen_name"      => $user->getShortCode(),
            "sex"              => $user->isFemale() ? 1 : 2,
            #"email"            => $user->getEmail(),
        ];

        $audio_status = $user->getCurrentAudioStatus();
        if(!is_null($audio_status)) 
            $return_object->audio_status = $audio_status->toVkApiStruct($user);

        return $return_object;
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

        $this->getUser()->updOnline($this->getPlatform());
        
        return 1;
    }

    function setOffline(): int
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
        $this->requireUser();
        
        return (object) [
            "friends"       => $this->getUser()->getFollowersCount(),
            "notifications" => $this->getUser()->getNotificationsCount(),
            "messages"      => $this->getUser()->getUnreadMessagesCount()
        ];

        # TODO: Filter
    }

    function saveProfileInfo(string $first_name = "", string $last_name = "", string $screen_name = "", int $sex = -1, int $relation = -1, string $bdate = "", int $bdate_visibility = -1, string $home_town = "", string $status = ""): object 
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        
        $user = $this->getUser();

        $output = [
            "changed" => 0,
        ];

        if(!empty($first_name) || !empty($last_name)) {
            $output["name_request"] = [
                "id" => random_int(1, 2048), # For compatibility with original VK API
                "status" => "success",
                "first_name" => !empty($first_name) ? $first_name : $user->getFirstName(),
                "last_name" => !empty($last_name) ? $last_name : $user->getLastName(),
            ];

            try {
                if(!empty($first_name))
                    $user->setFirst_name($first_name);
                if(!empty($last_name))
                    $user->setLast_Name($last_name);
            } catch (InvalidUserNameException $e) {
                $output["name_request"]["status"] = "declined";
                return (object) $output;
            }
        }

        if(!empty($screen_name))
            if (!$user->setShortCode($screen_name))
                $this->fail(1260, "Invalid screen name");

        # For compatibility with original VK API
        if($sex > 0)
            $user->setSex($sex == 1 ? 1 : 0);
            
        if($relation > -1)
            $user->setMarital_Status($relation);

        if(!empty($bdate)) {
            $birthday = strtotime($bdate);
            if (!is_int($birthday))
                $this->fail(100, "invalid value of bdate.");

            $user->setBirthday($birthday);
        }

        # For compatibility with original VK API
        switch($bdate_visibility) {
            case 0:
                $this->fail(946, "Hiding date of birth is not implemented.");
                break;
            case 1:
                $user->setBirthday_privacy(0);
                break;
            case 2:
                $user->setBirthday_privacy(1);
        }
        
        if(!empty($home_town))
            $user->setHometown($home_town);

        if(!empty($status)) 
            $user->setStatus($status);
        
        if($sex > 0 || $relation > -1 || $bdate_visibility > 1 || !empty("$first_name$last_name$screen_name$bdate$home_town$status")) {
            $output["changed"] = 1;
            $user->save();
        }

        return (object) $output;
    }

    function getBalance(): object
    {
        $this->requireUser();
        if(!OPENVK_ROOT_CONF['openvk']['preferences']['commerce'])
            $this->fail(105, "Commerce is disabled on this instance");
        
        return (object) ['votes' => $this->getUser()->getCoins()];
    }

    function getOvkSettings(): object
    {
        $this->requireUser();
        $user = $this->getUser();

        $settings_list = (object)[
            'avatar_style' => $user->getStyleAvatar(),
            'style'        => $user->getStyle(),
            'show_rating'  => !$user->prefersNotToSeeRating(),
            'nsfw_tolerance' => $user->getNsfwTolerance(),
            'post_view'    => $user->hasMicroblogEnabled() ? 'microblog' : 'old',
            'main_page'    => $user->getMainPage() == 0 ? 'my_page' : 'news',
        ];

        return $settings_list;
    }
}
