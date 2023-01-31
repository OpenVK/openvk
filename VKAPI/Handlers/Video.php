<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Entities\Video as VideoEntity;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Entities\Comment;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;

final class Video extends VKAPIRequestHandler
{
    function get(string $videos, int $offset = 0, int $count = 30, int $extended = 0): object
    {
        $this->requireUser();

        $vids = explode(',', $videos);

        foreach($vids as $vid)
        {
            $id    = explode("_", $vid);

            $items = [];

            $video = (new VideosRepo)->getByOwnerAndVID(intval($id[0]), intval($id[1]));
            if($video) {
                $items[] = [
                    "type" => "video",
                    "video" => [
                        "can_comment" => 1,
                        "can_like" => 0,  // we don't h-have wikes in videos
                        "can_repost" => 0,
                        "can_subscribe" => 1,
                        "can_add_to_faves" => 0,
                        "can_add" => 0,
                        "comments" => $video->getCommentsCount(),
                        "date" => $video->getPublicationTime()->timestamp(),
                        "description" => $video->getDescription(),
                        "duration" => 0, // я хуй знает как получить длину видео
                        "image" => [
                            [
                                "url" => $video->getThumbnailURL(),
                                "width" => 320,
                                "height" => 240,
                                "with_padding" => 1
                            ]
                        ],
                        "width" => 640,
                        "height" => 480,
                        "id" => $video->getVirtualId(),
                        "owner_id" => $video->getOwner()->getId(),
                        "user_id" => $video->getOwner()->getId(),
                        "title" => $video->getName(),
                        "is_favorite" => false,
                        "player" => $video->getURL(),
                        "added" => 0,
                        "repeat" => 0,
                        "type" => "video",
                        "views" => 0,
                        "likes" => [
                            "count" => 0,
                            "user_likes" => 0
                        ],
                        "reposts" => [
                            "count" => 0,
                            "user_reposted" => 0
                        ]
                    ]
                ];
            }
        }

        return (object) [
            "count" => count($items),
            "items" => $items
        ];
    }
}
