<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Exceptions\InvalidUserNameException;
use openvk\Web\Util\Validator;

final class Account extends VKAPIRequestHandler
{
    public function getProfileInfo(): object
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
        if (!is_null($audio_status)) {
            $return_object->audio_status = $audio_status->toVkApiStruct($user);
        }

        return $return_object;
    }

    public function getInfo(): object
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
            "own_posts_default"             => 0,
        ];
    }

    public function setOnline(): int
    {
        $this->requireUser();

        $this->getUser()->updOnline($this->getPlatform());

        return 1;
    }

    public function setOffline(): int
    {
        $this->requireUser();

        # Цiй метод є заглушка

        return 1;
    }

    public function getAppPermissions(): int
    {
        return 9355263;
    }

    public function getCounters(string $filter = ""): object
    {
        $this->requireUser();

        return (object) [
            "friends"       => $this->getUser()->getFollowersCount(),
            "notifications" => $this->getUser()->getNotificationsCount(),
            "messages"      => $this->getUser()->getUnreadMessagesCount(),
        ];

        # TODO: Filter
    }

    public function saveProfileInfo(string $first_name = "", string $last_name = "", string $screen_name = "", int $sex = -1, int $relation = -1, string $bdate = "", int $bdate_visibility = -1, string $home_town = "", string $status = "", string $telegram = null): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $user = $this->getUser();

        $output = [
            "changed" => 0,
        ];

        if (!empty($first_name) || !empty($last_name)) {
            $output["name_request"] = [
                "id" => random_int(1, 2048), # For compatibility with original VK API
                "status" => "success",
                "first_name" => !empty($first_name) ? $first_name : $user->getFirstName(),
                "last_name" => !empty($last_name) ? $last_name : $user->getLastName(),
            ];

            try {
                if (!empty($first_name)) {
                    $user->setFirst_name($first_name);
                }
                if (!empty($last_name)) {
                    $user->setLast_Name($last_name);
                }
            } catch (InvalidUserNameException $e) {
                $output["name_request"]["status"] = "declined";
                return (object) $output;
            }
        }

        if (!empty($screen_name)) {
            if (!$user->setShortCode($screen_name)) {
                $this->fail(1260, "Invalid screen name");
            }
        }

        # For compatibility with original VK API
        if ($sex > 0) {
            $user->setSex($sex == 1 ? 1 : 0);
        }

        if ($relation > -1 && $relation <= 8) {
            $user->setMarital_Status($relation);
        }

        if (!empty($bdate)) {
            $birthday = strtotime($bdate);
            if (!is_int($birthday) || $birthday > time()) {
                $this->fail(100, "invalid value of bdate.");
            }

            $user->setBirthday($birthday);
        }

        # For compatibility with original VK API
        switch ($bdate_visibility) {
            case 0:
                $this->fail(946, "Hiding date of birth is not implemented.");
                break;
            case 1:
                $user->setBirthday_privacy(0);
                break;
            case 2:
                $user->setBirthday_privacy(1);
        }

        if (!empty($home_town)) {
            $user->setHometown($home_town);
        }

        if (!empty($status)) {
            $user->setStatus($status);
        }

        if (!is_null($telegram)) {
            if (empty($telegram)) {
                $user->setTelegram(null);
            } elseif (Validator::i()->telegramValid($telegram)) {
                if (strpos($telegram, "t.me/") === 0) {
                    $user->setTelegram($telegram);
                } else {
                    $user->setTelegram(ltrim($telegram, "@"));
                }
            }
        }

        if ($sex > 0 || $relation > -1 || $bdate_visibility > 1 || !is_null($telegram) || !empty("$first_name$last_name$screen_name$bdate$home_town$status")) {
            $output["changed"] = 1;

            try {
                $user->save();
            } catch (\TypeError $e) {
                $output["changed"] = 0;
            }
        }

        return (object) $output;
    }

    public function getBalance(): object
    {
        $this->requireUser();
        if (!OPENVK_ROOT_CONF['openvk']['preferences']['commerce']) {
            $this->fail(-105, "Commerce is disabled on this instance");
        }

        return (object) ['votes' => $this->getUser()->getCoins()];
    }

    public function getOvkSettings(): object
    {
        $this->requireUser();
        $user = $this->getUser();

        $settings_list = (object) [
            'avatar_style' => $user->getStyleAvatar(),
            'style'        => $user->getStyle(),
            'show_rating'  => !$user->prefersNotToSeeRating(),
            'nsfw_tolerance' => $user->getNsfwTolerance(),
            'post_view'    => $user->hasMicroblogEnabled() ? 'microblog' : 'old',
            'main_page'    => $user->getMainPage() == 0 ? 'my_page' : 'news',
        ];

        return $settings_list;
    }

    public function sendVotes(int $receiver, int $value, string $message = ""): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"]) {
            $this->fail(-105, "Commerce is disabled on this instance");
        }

        if ($receiver < 0) {
            $this->fail(-248, "Invalid receiver id");
        }

        if ($value < 1) {
            $this->fail(-248, "Invalid value");
        }

        if (iconv_strlen($message) > 255) {
            $this->fail(-249, "Message is too long");
        }

        if ($this->getUser()->getCoins() < $value) {
            $this->fail(-252, "Not enough votes");
        }

        $receiver_entity = (new \openvk\Web\Models\Repositories\Users())->get($receiver);
        if (!$receiver_entity || $receiver_entity->isDeleted() || !$receiver_entity->canBeViewedBy($this->getUser())) {
            $this->fail(-250, "Invalid receiver");
        }

        if ($receiver_entity->getId() === $this->getUser()->getId()) {
            $this->fail(-251, "Can't transfer votes to yourself");
        }

        $this->getUser()->setCoins($this->getUser()->getCoins() - $value);
        $this->getUser()->save();

        $receiver_entity->setCoins($receiver_entity->getCoins() + $value);
        $receiver_entity->save();

        (new \openvk\Web\Models\Entities\Notifications\CoinsTransferNotification($receiver_entity, $this->getUser(), $value, $message))->emit();

        return (object) ['votes' => $this->getUser()->getCoins()];
    }

    public function ban(int $owner_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($owner_id < 0) {
            return 1;
        }

        if ($owner_id == $this->getUser()->getId()) {
            $this->fail(15, "Access denied: cannot blacklist yourself");
        }

        $config_limit = OPENVK_ROOT_CONF['openvk']['preferences']['blacklists']['limit'] ?? 100;
        $user_blocks  = $this->getUser()->getBlacklistSize();
        if (($user_blocks + 1) > $config_limit) {
            $this->fail(-7856, "Blacklist limit exceeded");
        }

        $entity = get_entity_by_id($owner_id);
        if (!$entity || $entity->isDeleted()) {
            return 0;
        }

        if ($entity->isBlacklistedBy($this->getUser())) {
            return 1;
        }

        $this->getUser()->addToBlacklist($entity);

        return 1;
    }

    public function unban(int $owner_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($owner_id < 0) {
            return 1;
        }

        if ($owner_id == $this->getUser()->getId()) {
            return 1;
        }

        $entity = get_entity_by_id($owner_id);
        if (!$entity) {
            return 0;
        }

        if (!$entity->isBlacklistedBy($this->getUser())) {
            return 1;
        }

        $this->getUser()->removeFromBlacklist($entity);

        return 1;
    }

    public function getBanned(int $offset = 0, int $count = 100, string $fields = ""): object
    {
        $this->requireUser();

        $result = (object) [
            'count' => $this->getUser()->getBlacklistSize(),
            'items' => [],
        ];
        $banned = $this->getUser()->getBlacklist($offset, $count);
        foreach ($banned as $ban) {
            if (!$ban) {
                continue;
            }
            $result->items[] = $ban->toVkApiStruct($this->getUser(), $fields);
        }

        return $result;
    }

    public function saveInterestsInfo(
        string $interests = null,
        string $fav_music = null,
        string $fav_films = null,
        string $fav_shows = null,
        string $fav_books = null,
        string $fav_quote = null,
        string $fav_games = null,
        string $about = null,
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $user = $this->getUser();
        $changes = 0;
        $changes_array = [
            "interests" => $interests,
            "fav_music" => $fav_music,
            "fav_films" => $fav_films,
            "fav_books" => $fav_books,
            "fav_shows" => $fav_shows,
            "fav_quote" => $fav_quote,
            "fav_games" => $fav_games,
            "about"     => $about,
        ];

        foreach ($changes_array as $change_name => $change_value) {
            $set_name = "set" . ucfirst($change_name);
            $get_name = "get" . str_replace("Fav", "Favorite", str_replace("_", "", ucfirst($change_name)));
            if (!is_null($change_value) && $change_value !== $user->$get_name()) {
                $user->$set_name(ovk_proc_strtr($change_value, 1000));
                $changes += 1;
            }
        }

        if ($changes > 0) {
            $user->save();
        }

        return (object) [
            "changed" => (int) ($changes > 0),
        ];
    }
}
