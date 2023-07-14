<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Posts as PostsRepo;

final class Likes extends VKAPIRequestHandler
{
    function add(string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        switch($type) {
            case "post":
                $post = (new PostsRepo)->getPostById($owner_id, $item_id);

                if(is_null($post) || $post->isDeleted())
                    $this->fail(100, "One of the parameters specified was missing or invalid: object not found");

                if($post->getWallOwner()->isDeleted()) {
                    $this->fail(665, "Error: Wall owner is deleted or not exist");
                }

                if(!$post->canBeViewedBy($this->getUser() ?? NULL)) {
                    $this->fail(2, "Access denied: you can't view this post.");
                }

                $post->setLike(true, $this->getUser());

                return (object) [
                    "likes" => $post->getLikesCount()
                ];
            default:
                $this->fail(100, "One of the parameters specified was missing or invalid: incorrect type");
        }
    }

    function delete(string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        switch($type) {
            case "post":
                $post = (new PostsRepo)->getPostById($owner_id, $item_id);
                if(is_null($post) || $post->isDeleted())
                    $this->fail(100, "One of the parameters specified was missing or invalid: object not found");

                if($post->getWallOwner()->isDeleted()) {
                    $this->fail(665, "Error: Wall owner is deleted or not exist");
                }

                if(!$post->canBeViewedBy($this->getUser() ?? NULL)) {
                    $this->fail(2, "Access denied: you can't view this post.");
                }

                $post->setLike(false, $this->getUser());
                return (object) [
                    "likes" => $post->getLikesCount()
                ];
            default:
                $this->fail(100, "One of the parameters specified was missing or invalid: incorrect type");
        }
    }
    
    function isLiked(int $user_id, string $type, int $owner_id, int $item_id): object
    {
        $this->requireUser();

        switch($type) {
            case "post":
                $user = (new UsersRepo)->get($user_id);
                if(is_null($user))
                    $this->fail(100, "One of the parameters specified was missing or invalid: user not found");

                if(!$user->canBeViewedBy($this->getUser()))
                    $this->fail(1983, "Access to user denied");
                
                $post = (new PostsRepo)->getPostById($owner_id, $item_id);
                if (is_null($post))
                    $this->fail(100, "One of the parameters specified was missing or invalid: object not found");
                
                if($post->getWallOwner()->isDeleted()) {
                    $this->fail(665, "Error: Wall owner is deleted or not exist");
                }

                if(!$post->canBeViewedBy($this->getUser() ?? NULL)) {
                    $this->fail(2, "Access denied: you can't view this post.");
                }

                if($post->getWallOwner()->isDeleted()) {
                    return (object) [
                        "liked"  => 0,
                    ];
                }

                return (object) [
                    "liked"  => (int) $post->hasLikeFrom($user),
                    "copied" => 0 # TODO: handle this
                ];
            default:
                $this->fail(100, "One of the parameters specified was missing or invalid: incorrect type");
        }
    }
}
