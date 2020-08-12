<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\Postable;
use openvk\Web\Models\Repositories\Posts as PostsRepo;

final class Wall extends VKAPIRequestHandler
{
    function get(string $owner_id, string $domain = "", int $offset = 0, int $count = 30, int $extended = 0): object
    {
        $posts = new PostsRepo;

        $items = [];

        foreach ($posts->getPostsFromUsersWall((int)$owner_id) as $post) {
            $items[] = (object)[
                "id" => $post->getVirtualId(),
                "from_id" => $post->getOwner()->getId(),
                "owner_id" => $post->getTargetWall(),
                "date" => $post->getPublicationTime()->timestamp(),
                "post_type" => "post",
                "text" => $post->getText(),
                "can_edit" => 0, // TODO
                "can_delete" => $post->canBeDeletedBy($this->getUser()),
                "can_pin" => 0, // TODO
                "can_archive" => false, // TODO MAYBE
                "is_archived" => false,
                "post_source" => (object)["type" => "vk"],
                "comments" => (object)[
                    "count" => $post->getCommentsCount(),
                    "can_post" => 1
                ],
                "likes" => (object)[
                    "count" => $post->getLikesCount(),
                    "user_likes" => (int) $post->hasLikeFrom($this->getUser()),
                    "can_like" => 1,
                    "can_publish" => 1,
                ],
                "reposts" => (object)[
                    "count" => $post->getRepostCount(),
                    "user_reposted" => 0
                ]
            ];
        }

        $profiles = [];
        $groups = [];

        $groups[0] = 'lol';
        $groups[2] = 'cec';

        if($extended == 1)
        return (object)[
            "items" => (array)$items,
            "cock" => (array)$groups
        ];
        else
        return (object)[
            "items" => (array)$items
        ];
    }
}
