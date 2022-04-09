<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Posts as PostsRepo;

final class Likes extends VKAPIRequestHandler
{
	function add(string $type, int $owner_id, int $item_id): object
	{
		$this->requireUser();

        switch ($type) {
            case 'post':
                $post = (new PostsRepo)->getPostById($owner_id, $item_id);
                if (is_null($post)) $this->fail(100, 'One of the parameters specified was missing or invalid: object not found');

                $post->setLike(true, $this->getUser());
                return (object)[
                    "likes" => $post->getLikesCount()
                ];
                break;
            default:
                $this->fail(100, 'One of the parameters specified was missing or invalid: incorrect type');
                break;
        }
	}

	function remove(string $type, int $owner_id, int $item_id): object
	{
		$this->requireUser();

        switch ($type) {
            case 'post':
                $post = (new PostsRepo)->getPostById($owner_id, $item_id);
                if (is_null($post)) $this->fail(100, 'One of the parameters specified was missing or invalid: object not found');

                $post->setLike(false, $this->getUser());
                return (object)[
                    "likes" => $post->getLikesCount()
                ];
                break;
            default:
                $this->fail(100, 'One of the parameters specified was missing or invalid: incorrect type');
                break;
        }
	}
	
    function isLiked(int $user_id, string $type, int $owner_id, int $item_id): object
	{
		$this->requireUser();

        switch ($type) {
            case 'post':
                $user = (new UsersRepo)->get($user_id);
                if (is_null($user)) return (object)[
                    "liked" => 0,
                    "copied" => 0,
                    "sex" => 0
                ];

                $post = (new PostsRepo)->getPostById($owner_id, $item_id);
                if (is_null($post)) $this->fail(100, 'One of the parameters specified was missing or invalid: object not found');
                
                return (object)[
                    "liked" => (int) $post->hasLikeFrom($user),
                    "copied" => 0 // TODO: handle this
                ];
                break;
            default:
                $this->fail(100, 'One of the parameters specified was missing or invalid: incorrect type');
                break;
        }
	}
}
