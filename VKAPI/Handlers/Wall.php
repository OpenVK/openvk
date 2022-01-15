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
        $profiles = [];
        $groups = [];
        $count = $posts->getPostCountOnUserWall((int) $owner_id);

        foreach ($posts->getPostsFromUsersWall((int)$owner_id, 1, $count, $offset) as $post) {
            $from_id = get_class($post->getOwner()) == "openvk\Web\Models\Entities\Club" ? $post->getOwner()->getId() * (-1) : $post->getOwner()->getId();
            $items[] = (object)[
                "id" => $post->getVirtualId(),
                "from_id" => $from_id,
                "owner_id" => $post->getTargetWall(),
                "date" => $post->getPublicationTime()->timestamp(),
                "post_type" => "post",
                "text" => $post->getText(),
                "can_edit" => 0, // TODO
                "can_delete" => $post->canBeDeletedBy($this->getUser()),
                "can_pin" => $post->canBePinnedBy($this->getUser()),
                "can_archive" => false, // TODO MAYBE
                "is_archived" => false,
                "is_pinned" => $post->isPinned(),
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
            
            if ($from_id > 0)
                $profiles[] = $from_id;
            else
                $groups[] = $from_id * -1;
        }

        if($extended == 1) 
        {
            $profiles = array_unique($profiles);
            $groups = array_unique($groups);

            $profilesFormatted = [];
            $groupsFormatted = [];

            foreach ($profiles as $prof) {
                $user = (new UsersRepo)->get($prof);
                $profilesFormatted[] = (object)[
                    "first_name" => $user->getFirstName(),
                    "id" => $user->getId(),
                    "last_name" => $user->getLastName(),
                    "can_access_closed" => false,
                    "is_closed" => false,
                    "sex" => $user->isFemale() ? 1 : 2,
                    "screen_name" => $user->getShortCode(),
                    "photo_50" => $user->getAvatarUrl(),
                    "photo_100" => $user->getAvatarUrl(),
                    "online" => $user->isOnline()
                ];
            }

            foreach($groups as $g) {
                $group = (new ClubsRepo)->get($g);
                $groupsFormatted[] = (object)[
                    "id" => $group->getId(),
                    "name" => $group->getName(),
                    "screen_name" => $group->getShortCode(),
                    "is_closed" => 0,
                    "type" => "group",
                    "photo_50" => $group->getAvatarUrl(),
                    "photo_100" => $group->getAvatarUrl(),
                    "photo_200" => $group->getAvatarUrl(),
                ];
            }

            return (object)[
                "count" => $count,
                "items" => (array)$items,
                "profiles" => (array)$profilesFormatted,
                "groups" => (array)$groupsFormatted
            ];
        }
        else
            return (object)[
                "count" => $count,
                "items" => (array)$items
            ];
    }

    function post(string $owner_id, string $message, int $from_group = 0, int $signed = 0): object
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

        $anon = OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["enable"];
        if($wallOwner instanceof Club && $from_group == 1 && $signed != 1 && $anon) {
            $manager = $wallOwner->getManager($this->getUser());
            if($manager)
                $anon = $manager->isHidden();
            elseif($this->getUser()->getId() === $wallOwner->getOwner()->getId())
                $anon = $wallOwner->isOwnerHidden();
        } else {
            $anon = false;
        }

        $flags = 0;
        if($from_group == 1 && $wallOwner instanceof Club && $wallOwner->canBeModifiedBy($this->getUser()))
            $flags |= 0b10000000;
        if($signed == 1)
            $flags |= 0b01000000;

        // TODO: Compatible implementation of this
        try {
            $photo = null;
            $video = null;
            if($_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
                $album = null;
                if(!$anon && $owner_id > 0 && $owner_id === $this->getUser()->getId())
                    $album = (new AlbumsRepo)->getUserWallAlbum($wallOwner);

                $photo = Photo::fastMake($this->getUser()->getId(), $message, $_FILES["photo"], $album, $anon);
            }

            if($_FILES["video"]["error"] === UPLOAD_ERR_OK)
                $video = Video::fastMake($this->getUser()->getId(), $message, $_FILES["video"], $anon);
        } catch(\DomainException $ex) {
            $this->fail(-156, "The media file is corrupted");
        } catch(ISE $ex) {
            $this->fail(-156, "The media file is corrupted or too large ");
        }

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

        if(!is_null($photo))
            $post->attach($photo);

        if(!is_null($video))
            $post->attach($video);

        if($wall > 0 && $wall !== $this->user->identity->getId())
            (new WallPostNotification($wallOwner, $post, $this->user->identity))->emit();

        return (object)["post_id" => $post->getVirtualId()];
    }
}
