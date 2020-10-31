<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Notifications\{WallPostNotification};
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
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

    function post(string $owner_id, string $message, int $from_group = 0): object
    {
        $this->requireUser();

        $owner_id = intval($owner_id);
        
        $wallOwner = ($owner_id > 0 ? (new UsersRepo)->get($owner_id) : (new ClubsRepo)->get($owner_id * -1))
                     ?? $this->fail(18, "User was deleted or banned");
        if($owner_id > 0)
            $canPost = $wallOwner->getPrivacyPermission("wall.write", $this->getUser());
        else if($owner_id < 0)
            if($wallOwner->canBeModifiedBy($this->getUser()))
                $canPost = true;
            else
                $canPost = $wallOwner->canPost();
        else
            $canPost = false; 

        if($canPost == false) $this->fail(15, "Access denied");

        $flags = 0;
        if($from_group == 1)
            $flags |= 0b10000000;

        try {
            $post = new Post;
            $post->setOwner($this->getUser()->getId());
            $post->setWall($owner_id);
            $post->setCreated(time());
            $post->setContent($message);
            $post->setFlags($flags);
            $post->save();
        } catch(\LogicException $ex) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        }

        if($wall > 0 && $wall !== $this->user->identity->getId())
            (new WallPostNotification($wallOwner, $post, $this->user->identity))->emit();

        return (object)["post_id" => $post->getVirtualId()];
    }
}
