<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Document;
use openvk\Web\Models\Repositories\{Photos, Clubs, Albums, Videos, Notes, Audios, Documents};
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Gifts as GiftsRepo;

final class Execute extends VKAPIRequestHandler
{
    public function nuggets(): string
    {
        return "котлетки";
    }

    public function getUserInfo(string $fields = 'photo_100,photo_50,exports,country,sex,status,bdate,first_name_gen,last_name_gen,verified'): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $profile = $this->getUser()->toVkApiStruct(null, $fields);
        $info = (object) [
            "country" => "AM",
            "https_required" => 0,
            "intro" => 0,
            "lang" => 0,
            "support_url" => ovk_scheme(true) . $_SERVER["HTTP_HOST"] . '/support',
            "money_p2p_params" => (object) [
                "min_amount" => 100,
                "max_amount" => 75000,
                "currency" => "SPAMTON",
            ],
            "audio_ads" => (object) [
                "day_limit" => 10000,
                "track_limit" => 10000,
                "types_allowed" => [],
                "sections" => ["my","user_playlists","group_playlists","my_playlists","recent","audio_feed","recs",
                    "recs_audio","recs_album","search","global_search","group_list","user_list",
                    "user_wall","group_wall","feed","other"],
            ],
            "profiler_settings" => (object) [
                "api_requests" => true,
                "download_patterns" => [],
                "raise_to_record_enabled" => true,
                "music_intro" => false,
                "settings" => [
                    (object) [
                        "name" => "audio_ads",
                        "available" => false,
                    ],
                    (object) [
                        "name" => "audio_background_limit",
                        "available" => false,
                        "value" => "1440",
                    ],
                    (object) [
                        "name" => "gif_autoplay",
                        "available" => true,
                    ],
                    (object) [
                        "name" => "audio_restrictions",
                        "available" => false,
                    ],
                    (object) [
                        "name" => "stories",
                        "available" => false,
                    ],
                    (object) [
                        "name" => "masks",
                        "available" => false,
                    ],
                    (object) [
                        "name" => "video_autoplay",
                        "available" => true,
                    ],
                ],
                "community_comments" => false,
            ],
        ];
        $counters = (object) [
            "friends"       => $this->getUser()->getFollowersCount(),
            "messages"      => $this->getUser()->getUnreadMessagesCount(),
            "photos"        => 0,
            "videos"        => 0,
            "groups"        => 0,
            "notifications" => $this->getUser()->getNotificationsCount(),
            "sdk"           => 0,
            "app_requests"  => 0,
        ];
        $newsdata = (object) [
            "lists" => [],
            "sections" => [],
            "feed_type" => "top",
            "refresh_timeout_recent" => 600000,
            "refresh_timeout_top" => 600000,
            "refresh_timeout_recommended" => 600000,
            "items" => [],
            "profiles" => [],
            "groups" => [],
        ];

        return (object) [
            'profile' => $profile,
            'info' => $info,
            'counters' => $counters,
            'newsfeed' => $newsdata,
            'time' => time(),
            'allow_buy_votes' => 1,
            'ads_stoplist' => [],
            'show_html_games' => 0,
            'defaultAudioPlayer' => 'standard',
        ];
    }

    public function wallGetWrapNew(int $owner_id = 1, int $photo_sizes = 1, int $offset = 25, int $count = 25, string $extended = ''): object
    {
        $response = (object) [
            "count" => 0,
            "items" => [
                /*[
                    "id" => 12345,
                    "owner_id" => 1,
                    "from_id" => 1,
                    "date" => time(),
                    "post_type" => "post",
                    "text" => "хуй",
                    "attachments" => [] 
                ]*/
            ],
            
            "profiles" => [
                /*[
                    "id" => 1,
                    "first_name" => "seks",
                    "last_name" => "pro",
                    "sex" => 2,
                    "photo_50" => "https://vk.com/images/camera_50.png",
                    "photo_100" => "https://vk.com/images/camera_100.png"
                ]*/
            ],
            "groups" => [
                /*
                [
                    "id" => 1, 
                    "name" => "",
                    "photo_50" => "",
                    "photo_100" => ""
                ]
                */
            ],
            // закреп
            "fixed" => null, 

            "status" => [
                // "text" => ""
                // "audio" => []
            ],
            // "postponed_count" => 0,
            // "suggested_count" => 0
        ];
        return $response;
    }
    
    public function getFullProfileNewWithGifts(int $user_id = 1, int $photo_count = 25, int $gift_count = 25): object
    {
        // вк 3.11 для костыльных групп
        $this->requireUser();
        $this->willExecuteWriteAction();

        $users = new UsersRepo();

        $user = $users->get($user_id);

        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];


        if (is_null($user)) {
            $response = (object) [
                "id" 		  => 0,
                "first_name"  => "DELETED",
                "last_name"   => "",
                "deactivated" => "deleted",
            ];
            return $response;
        } elseif ($user->isDeleted()) {
            $response = (object) [
                "id" 		  => $user->getId(),
                "first_name"  => "DELETED",
                "last_name"   => "",
                "deactivated" => "deleted",
            ];
        } elseif ($user->isBanned()) {
            $response = (object) [
                "id"          => $user->getId(),
                "first_name"  => $user->getFirstName(true),
                "last_name"   => $user->getLastName(true),
                "deactivated" => "banned",
                "ban_reason"  => $user->getBanReason(),
            ];
        } else {
            $canView = $user->canBeViewedBy($this->getUser());
            $response = (object) [
                "id"                => $user->getId(),
                "first_name"        => $user->getFirstName(true),
                "last_name"         => $user->getLastName(true),
                "is_closed"         => (int) $user->isClosed(),
                "can_access_closed" => (int) $canView,
            ];
        }
        
        if ($this->getUser()->getId() != $user->getId()) {
            $response->is_favorite = 0; // stub
        }

        $sub_status = $user->getSubscriptionStatus($this->getUser());
        switch ($sub_status) {
            case 1:
                $response->friend_status = 2;
                break;
            case 2:
                $response->friend_status = 1;
                break;
            default:
                $response->friend_status = $sub_status;
                break;
        }
        $response->can_send_friend_request = (int) !((bool) $response->friend_status);
        
        if ($user->getShortCode() != null) {
            $response->screen_name = $user->getShortCode();
        }
        
        $response->first_name_dat = $user->getMorphedName("dative", false, false);
        $response->first_name_gen = $user->getMorphedName("genitive", false, false);
        $response->first_name_ins = $user->getMorphedName("ablative", false, false);
        $response->first_name_acc = $user->getMorphedName("accusative", false, false);
        $response->last_name_dat = $user->getMorphedName("dative", false, true);
        $response->last_name_gen = $user->getMorphedName("genitive", false, true);
        $response->last_name_ins = $user->getMorphedName("ablative", false, true);
        $response->last_name_acc = $user->getMorphedName("accusative", false, true);
        
        $response->verified = (int) $user->isVerified();
        
        $response->sex = $user->isFemale() ? 1 : ($user->isNeutral() ? 0 : 2); // no experience

        $response->has_photo = is_null($user->getAvatarPhoto()) ? 0 : 1;
        $response->photo_rec = $user->getAvatarURL(); // rare
        $response->photo_medium_rec = $user->getAvatarURL("normal"); // medium well
        $response->photo_max = $user->getAvatarURL("normal"); // well done

        if ($user->getStatus() != null) {
            $response->activity = $user->getStatus();
        }

        $audioStatus = $user->getCurrentAudioStatus();
        if ($audioStatus) {
            $response->status = [$audioStatus->toVkApiStruct()];
        } else {
            $response->status = (object) [];
        }

        $response->can_write_private_message = (int) $user->getPrivacyPermission("messages.write");
        $response->can_post = (int) $user->getPrivacyPermission("wall.write");
        $response->can_see_all_posts = (int) $user->getPrivacyPermission("page.read");
        $response->wall_default = "all"; // stub
        $response->can_call = 0; // stub
        $response->blacklisted_by_me = (int) $user->isBlacklistedBy($this->getUser());
        $response->verified = $user->isVerified();

        if ($user->onlineStatus() == 0) {
            $platform = $user->getOnlinePlatform(true);
            switch ($platform) {
                case 'iphone':
                    $platform = 2;
                    break;

                case 'android':
                    $platform = 4;
                    break;

                case 'web':
                case null:
                    $platform = 7;
                    break;

                default:
                    $platform = 1;
                    break;
            }
            $response->last_seen = (object) [
                "platform" => $platform,
                "time"     => $user->getOnline()->timestamp(),
            ];
        }

        if ($canView) {
            $response->cities = []; // stub

            $response->relation = $user->getMaritalStatus();
            $rp = $user->getMaritalStatusUser();
            if ($rp) {
                $response->relation_partner = $rp->toVkApiStruct();
            }

            $birthday = $user->getBirthday();
            switch ($user->getBirthdayPrivacy()) {
                case 1:
                    $response->bdate = $birthday->format('%d.%m');
                    break;
                case 2:
                    $response->bdate = $birthday->format('%d.%m.%Y');
                    break;
            }
            
            $city = $user->getCity();
            if ($city) {
                $response->city = (object) ["title" => $city];
                $response->country = (object) ["title" => ""]; // stub, required to show city
            }

            //$response->mobile_phone = "";
            //$response->home_phone = "";
            //$response->schools = [];
            //$response->universities = [];
            //$response->relatives = [];
            //$response->relatives_profiles = [];

            $books = $user->getFavoriteBooks();
            if ($books) $response->books = $books;

            $games = $user->getFavoriteGames();
            if ($games) $response->games = $games;

            $music = $user->getFavoriteMusic();
            if ($music) $response->music = $music;

            $quotes = $user->getFavoriteQuote();
            if ($quotes) $response->quotes = $quotes;

            $interests = $user->getInterests();
            if ($interests) $response->interests = $interests;

            $movies = $user->getFavoriteFilms();
            if ($movies) $response->movies = $movies;

            $tv = $user->getFavoriteShows();
            if ($tv) $response->tv = $tv;

            $about = $user->getDescription();
            if ($about) $response->about = $about;

            $home_town = $user->getHometown();
            if ($home_town) $response->home_town = $home_town;

            $site = $user->getWebsite();
            if ($site) $response->site = $site;

            // $response->activities = "";

            $response->personal = (object) [
                // "langs" => [],
                // "religion" => "fgfgfg",
                // "life_main" => 1,
                // "people_main" => 1,
                // "inspired_by" => "",
                // "smoking" => 1,
                // "alcohol" => 1,
            ];

            $pol_views = $user->getPoliticalViews();
            if ($pol_views) $response->personal->political = $pol_views;

            $gifts = new GiftsRepo();
            
            $user_gifts = array_slice(iterator_to_array($user->getGifts(1, $gift_count)), 0, $gift_count);

            foreach ($user_gifts as $gift) {
                $gift_item[] = [
                    "id"        => $gift->id,
                    "from_id"   => $gift->anon == true ? 0 : $gift->sender->getId(),
                    "message"   => $gift->caption == null ? "" : $gift->caption,
                    "date"      => $gift->sent->timestamp(),
                    "privacy"   => $gift->anon == true ? 1 : 0,
                    "gift_hash" => "c00lb00bi3s",
                    "gift"      => [
                        "id"          => $gift->gift->getId(),
                        "thumb_256"   => $server_url . $gift->gift->getImage(2),
                        "thumb_96"    => $server_url . $gift->gift->getImage(2),
                        "thumb_48"    => $server_url . $gift->gift->getImage(2),
                    ],
                ];
            }
            
            if ($user->getGiftCount() > 0) {
                $response->gifts = [
                    "count" => $user->getGiftCount(),
                    "items" => $gift_item
                ];
            }
        }
        
        $photos = new Photos();
        $response->counters = (object) [
            "friends" => $user->getFriendsCount(),
            "photos"  => $photos->getUserPhotosCount($user),
            "videos"  => (new Videos())->getUserVideosCount($user),
            "audios"  => (new Audios())->getUserCollectionSize($user),
            "notes"   => (new Notes())->getUserNotesCount($user),
            "groups"  => $user->getClubCount(),
            "online_friends" => $user->getFriendsOnlineCount(),
            "mutual_friends" => 0, // stub
            "user_photos" => 0, // stub
            "albums" => (new Albums())->getUserAlbumsCount($user),
            "followers" => $user->getFollowersCount(),
            "gifts" => $user->getGiftCount(),
        ];

        $response->photos = [];

        if ($user->getPrivacyPermission('photos.read', $this->getUser()) && $response->counters->photos > 0) {
            $response->photos["count"] = $response->counters->photos; 

            $evphoto = $photos->getEveryUserPhoto($user, 0, $photo_count);

            foreach ($evphoto as $photo) {
                if (!$photo || $photo->isDeleted()) {
                    continue;
                } 
                $response->photos["items"][] = $photo->toVkApiStruct(true, false);
            }
        }

        return $response;
    }

    public function getNewsfeedWithPromo(string $fields = "", string $start_from = "", int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 1)
    {
        // alias of newsfeed.get
        $newsfeed = $this->createHandler(Newsfeed::class);
        return $newsfeed->get($fields, $start_from, $start_time, $end_time, $offset, $count, $extended, 0);
    }

    public function getNewsfeedSmart(string $fields = "", string $start_from = "", int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 1)
    {
        // alias of newsfeed.get
        $newsfeed = $this->createHandler(Newsfeed::class);
        return $newsfeed->get($fields, $start_from, $start_time, $end_time, $offset, $count, $extended, 0);
    }

    public function getProfiles(string $user_ids = "0", string $fields = "", string $relation_case = "def", int $offset = 0, int $count = 100)
    {
        // alias of users.get, used in VK 3.10 Android app
        $users = $this->createHandler(Users::class);
        return $users->get($user_ids, $fields, $offset, $count);
    }
}
