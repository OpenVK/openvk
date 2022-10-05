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
}
