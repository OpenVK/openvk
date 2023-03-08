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
                $items[] = $video->getApiStructure();
            }
        }

        return (object) [
            "count" => count($items),
            "items" => $items
        ];
    }
}
