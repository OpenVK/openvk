<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\{User, Report};
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\{Photos, Clubs, Albums, Videos, Notes, Audios};
use openvk\Web\Models\Repositories\Reports;

final class Users extends VKAPIRequestHandler
{
    public function get(string $user_ids = "0", string $fields = "", int $offset = 0, int $count = 100, User $authuser = null /* костыль(( */): array
    {
        if ($authuser == null) {
            $authuser = $this->getUser();
        }

        $users = new UsersRepo();
        if ($user_ids == "0") {
            if (!$authuser) {
                return [];
            }

            $user_ids = (string) $authuser->getId();
        }


        $usrs = explode(',', $user_ids);
        $response = [];

        $ic = sizeof($usrs);

        if (sizeof($usrs) > $count) {
            $ic = $count;
        }

        $usrs = array_slice($usrs, $offset * $count);

        for ($i = 0; $i < $ic; $i++) {
            if ($usrs[$i] != 0) {
                $usr = $users->get((int) $usrs[$i]);
                if (is_null($usr) || $usr->isDeleted()) {
                    $response[$i] = (object) [
                        "id" 		  => (int) $usrs[$i],
                        "first_name"  => "DELETED",
                        "last_name"   => "",
                        "deactivated" => "deleted",
                    ];
                } elseif ($usr->isBanned()) {
                    $response[$i] = (object) [
                        "id"          => $usr->getId(),
                        "first_name"  => $usr->getFirstName(true),
                        "last_name"   => $usr->getLastName(true),
                        "deactivated" => "banned",
                        "ban_reason"  => $usr->getBanReason(),
                    ];
                } elseif ($usrs[$i] == null) {

                } else {
                    $response[$i] = (object) [
                        "id"                => $usr->getId(),
                        "first_name"        => $usr->getFirstName(true),
                        "last_name"         => $usr->getLastName(true),
                        "is_closed"         => $usr->isClosed(),
                        "can_access_closed" => (bool) $usr->canBeViewedBy($this->getUser()),
                    ];

                    $flds = explode(',', $fields);
                    $canView = $usr->canBeViewedBy($this->getUser());
                    foreach ($flds as $field) {
                        switch ($field) {
                            case "verified":
                                $response[$i]->verified = intval($usr->isVerified());
                                break;
                            case "sex":
                                $response[$i]->sex = $usr->isFemale() ? 1 : ($usr->isNeutral() ? 0 : 2);
                                break;
                            case "has_photo":
                                $response[$i]->has_photo = is_null($usr->getAvatarPhoto()) ? 0 : 1;
                                break;
                            case "photo_max_orig":
                                $response[$i]->photo_max_orig = $usr->getAvatarURL();
                                break;
                            case "photo_max":
                                $response[$i]->photo_max = $usr->getAvatarURL("original");
                                break;
                            case "photo_50":
                                $response[$i]->photo_50 = $usr->getAvatarURL();
                                break;
                            case "photo_100":
                                $response[$i]->photo_100 = $usr->getAvatarURL("tiny");
                                break;
                            case "photo_200":
                                $response[$i]->photo_200 = $usr->getAvatarURL("normal");
                                break;
                            case "photo_200_orig": # вообще не ебу к чему эта строка ну пусть будет кек
                                $response[$i]->photo_200_orig = $usr->getAvatarURL("normal");
                                break;
                            case "photo_400_orig":
                                $response[$i]->photo_400_orig = $usr->getAvatarURL("normal");
                                break;

                                # Она хочет быть выебанной видя матан
                                # Покайфу когда ты Виет а вокруг лишь дискриминант

                                # ору а когда я это успел написать
                                # вова кстати не матерись в коде мамка же спалит азщазаззазщазазаззазазазх
                            case "status":
                                if ($usr->getStatus() != null) {
                                    $response[$i]->status = $usr->getStatus();
                                }

                                $audioStatus = $usr->getCurrentAudioStatus();

                                if ($audioStatus) {
                                    $response[$i]->status_audio = $audioStatus->toVkApiStruct();
                                }

                                break;
                            case "screen_name":
                                if ($usr->getShortCode() != null) {
                                    $response[$i]->screen_name = $usr->getShortCode();
                                }
                                break;
                            case "friend_status":
                                switch ($usr->getSubscriptionStatus($authuser)) {
                                    case 3:
                                        # NOTICE falling through
                                    case 0:
                                        $response[$i]->friend_status = $usr->getSubscriptionStatus($authuser);
                                        break;
                                    case 1:
                                        $response[$i]->friend_status = 2;
                                        break;
                                    case 2:
                                        $response[$i]->friend_status = 1;
                                        break;
                                }
                                break;
                            case "last_seen":
                                if ($usr->onlineStatus() == 0) {
                                    $platform = $usr->getOnlinePlatform(true);
                                    switch ($platform) {
                                        case 'iphone':
                                            $platform = 2;
                                            break;

                                        case 'android':
                                            $platform = 4;
                                            break;

                                        case null:
                                            $platform = 7;
                                            break;

                                        default:
                                            $platform = 1;
                                            break;
                                    }

                                    $response[$i]->last_seen = (object) [
                                        "platform" => $platform,
                                        "time"     => $usr->getOnline()->timestamp(),
                                    ];
                                }
                                // no break
                            case "music":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->music = $usr->getFavoriteMusic();
                                break;
                            case "movies":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->movies = $usr->getFavoriteFilms();
                                break;
                            case "tv":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->tv = $usr->getFavoriteShows();
                                break;
                            case "books":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->books = $usr->getFavoriteBooks();
                                break;
                            case "city":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->city = $usr->getCity();
                                break;
                            case "interests":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->interests = $usr->getInterests();
                                break;
                            case "quotes":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->quotes = $usr->getFavoriteQuote();
                                break;
                            case "games":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->games = $usr->getFavoriteGames();
                                break;
                            case "email":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->email = $usr->getContactEmail();
                                break;
                            case "telegram":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->telegram = $usr->getTelegram();
                                break;
                            case "about":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->about = $usr->getDescription();
                                break;
                            case "rating":
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->rating = $usr->getRating();
                                break;
                            case "counters":
                                $response[$i]->counters = (object) [
                                    "friends_count" => $usr->getFriendsCount(),
                                    "photos_count" => (new Photos())->getUserPhotosCount($usr),
                                    "videos_count" => (new Videos())->getUserVideosCount($usr),
                                    "audios_count" => (new Audios())->getUserCollectionSize($usr),
                                    "notes_count" => (new Notes())->getUserNotesCount($usr),
                                ];
                                break;
                            case "correct_counters":
                                $response[$i]->counters = (object) [
                                    "friends" => $usr->getFriendsCount(),
                                    "photos"  => (new Photos())->getUserPhotosCount($usr),
                                    "videos"  => (new Videos())->getUserVideosCount($usr),
                                    "audios"  => (new Audios())->getUserCollectionSize($usr),
                                    "notes"   => (new Notes())->getUserNotesCount($usr),
                                    "groups"  => $usr->getClubCount(),
                                    "online_friends" => $usr->getFriendsOnlineCount(),
                                ];
                                break;
                            case "guid":
                                $response[$i]->guid = $usr->getChandlerGUID();
                                break;
                            case 'background':
                                $backgrounds = $usr->getBackDropPictureURLs();
                                $response[$i]->background = $backgrounds;
                                break;
                            case 'reg_date':
                                if (!$canView) {
                                    break;
                                }

                                $response[$i]->reg_date = $usr->getRegistrationTime()->timestamp();
                                break;
                            case 'is_dead':
                                $response[$i]->is_dead = $usr->isDead();
                                break;
                            case 'nickname':
                                $response[$i]->nickname = $usr->getPseudo();
                                break;
                            case 'blacklisted_by_me':
                                if (!$authuser) {
                                    break;
                                }

                                $response[$i]->blacklisted_by_me = (int) $usr->isBlacklistedBy($this->getUser());
                                break;
                            case 'blacklisted':
                                if (!$authuser) {
                                    break;
                                }

                                $response[$i]->blacklisted = (int) $this->getUser()->isBlacklistedBy($usr);
                                break;
                            case "custom_fields":
                                if (sizeof($usrs) > 1) {
                                    break;
                                }

                                $c_fields = \openvk\Web\Models\Entities\UserInfoEntities\AdditionalField::getByOwner($usr->getId());
                                $append_array = [];
                                foreach ($c_fields as $c_field) {
                                    $append_array[] = $c_field->toVkApiStruct();
                                }

                                $response[$i]->custom_fields = $append_array;
                                break;
                            case "bdate":
                                if (!$canView) {
                                    $response[$i]->bdate = "01.01.1970";
                                    break;
                                }
                                $visibility = $usr->getBirthdayPrivacy();
                                $response[$i]->bdate_visibility = $visibility;

                                $birthday = $usr->getBirthday();
                                if ($birthday) {
                                    switch ($visibility) {
                                        case 1:
                                            $response[$i]->bdate = $birthday->format('%d.%m');
                                            break;
                                        case 2:
                                            $response[$i]->bdate = $birthday->format('%d.%m.%Y');
                                            break;
                                        case 0:
                                        default:
                                            $response[$i]->bdate = null;
                                            break;
                                    }
                                } else {
                                    $response[$i]->bdate = null;
                                }
                                break;
                        }
                    }

                    if ($usr->getOnline()->timestamp() + 300 > time()) {
                        $response[$i]->online = 1;
                    } else {
                        $response[$i]->online = 0;
                    }
                }
            }
        }

        return $response;
    }

    public function getFollowers(int $user_id, string $fields = "", int $offset = 0, int $count = 100): object
    {
        $offset++;
        $followers = [];

        $users = new UsersRepo();

        $this->requireUser();

        $user = $users->get($user_id);

        if (!$user || $user->isDeleted()) {
            $this->fail(14, "Invalid user");
        }

        if (!$user->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        foreach ($users->get($user_id)->getFollowers($offset, $count) as $follower) {
            $followers[] = $follower->getId();
        }

        $response = $followers;

        if (!is_null($fields)) {
            $response = $this->get(implode(',', $followers), $fields, 0, $count);
        }

        return (object) [
            "count" => $users->get($user_id)->getFollowersCount(),
            "items" => $response,
        ];
    }

    public function search(
        string $q,
        string $fields = "",
        int $offset = 0,
        int $count = 100,
        string $city = "",
        string $hometown = "",
        int $sex = 3,
        int $status = 0, # marital_status
        bool $online = false,
        # non standart params:
        int $sort = 0,
        int $polit_views = 0,
        string $fav_music = "",
        string $fav_films = "",
        string $fav_shows = "",
        string $fav_books = "",
        string $interests = ""
    ) {
        if ($count > 100) {
            $this->fail(100, "One of the parameters specified was missing or invalid: count should be less or equal to 100");
        }

        $users = new UsersRepo();
        $output_sort = ['type' => 'id', 'invert' => false];
        $output_params = [
            "ignore_private" => true,
        ];

        switch ($sort) {
            default:
            case 0:
                $output_sort = ['type' => 'id', 'invert' => false];
                break;
            case 1:
                $output_sort = ['type' => 'id', 'invert' => true];
                break;
            case 4:
                $output_sort = ['type' => 'rating', 'invert' => false];
                break;
        }

        if (!empty($city)) {
            $output_params['city'] = $city;
        }

        if (!empty($hometown)) {
            $output_params['hometown'] = $hometown;
        }

        if ($sex != 3) {
            $output_params['gender'] = $sex;
        }

        if ($status != 0) {
            $output_params['marital_status'] = $status;
        }

        if ($polit_views != 0) {
            $output_params['polit_views'] = $polit_views;
        }

        if (!empty($interests)) {
            $output_params['interests'] = $interests;
        }

        if (!empty($fav_music)) {
            $output_params['fav_music'] = $fav_music;
        }

        if (!empty($fav_films)) {
            $output_params['fav_films'] = $fav_films;
        }

        if (!empty($fav_shows)) {
            $output_params['fav_shows'] = $fav_shows;
        }

        if (!empty($fav_books)) {
            $output_params['fav_books'] = $fav_books;
        }

        if ($online) {
            $output_params['is_online'] = 1;
        }

        $array = [];
        $find  = $users->find($q, $output_params, $output_sort);

        foreach ($find->offsetLimit($offset, $count) as $user) {
            $array[] = $user->getId();
        }

        if (!$array || sizeof($array) < 1) {
            return (object) [
                "count" => 0,
                "items" => [],
            ];
        }

        return (object) [
            "count" => $find->size(),
            "items" => $this->get(implode(',', $array), $fields),
        ];
    }

    public function report(int $user_id, string $type = "spam", string $comment = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($user_id == $this->getUser()->getId()) {
            $this->fail(12, "Can't report yourself.");
        }

        if (sizeof(iterator_to_array((new Reports())->getDuplicates("user", $user_id, null, $this->getUser()->getId()))) > 0) {
            return 1;
        }

        $report = new Report();
        $report->setUser_id($this->getUser()->getId());
        $report->setTarget_id($user_id);
        $report->setType("user");
        $report->setReason($comment);
        $report->setCreated(time());
        $report->save();

        return 1;
    }
}
