<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Document;
use openvk\Web\Models\Repositories\Documents;

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
                "currency" => "SPAMTON"
            ],
            "audio_ads" => (object) [
                "day_limit" => 10000,
                "track_limit" => 10000,
                "types_allowed" => [],
                "sections" => ["my","user_playlists","group_playlists","my_playlists","recent","audio_feed","recs",
                               "recs_audio","recs_album","search","global_search","group_list","user_list",
                               "user_wall","group_wall","feed","other"]
            ],
            "profiler_settings" => (object) [
                "api_requests" => true,
                "download_patterns" => [],
                "raise_to_record_enabled" => true,
                "music_intro" => false,
                "settings" => [
                    (object) [
                        "name" => "audio_ads",
                        "available" => false
                    ],
                    (object) [
                        "name" => "audio_background_limit",
                        "available" => false,
                        "value" => "1440"
                    ],
                    (object) [
                        "name" => "gif_autoplay",
                        "available" => true
                    ],
                    (object) [
                        "name" => "audio_restrictions",
                        "available" => false
                    ],
                    (object) [
                        "name" => "stories",
                        "available" => false
                    ],
                    (object) [
                        "name" => "masks",
                        "available" => false
                    ],
                    (object) [
                        "name" => "video_autoplay",
                        "available" => true
                    ]
                ],
                "community_comments" => false
            ]
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
            "groups" => []
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
            'defaultAudioPlayer' => 'standard'
        ];
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
}
