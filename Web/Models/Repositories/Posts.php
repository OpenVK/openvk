<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Posts
{
    private $context;
    private $posts;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->posts   = $this->context->table("posts");
    }
    
    private function toPost(?ActiveRow $ar): ?Post
    {
        return is_null($ar) ? NULL : new Post($ar);
    }
    
    function get(int $id): ?Post
    {
        return $this->toPost($this->posts->get($id));
    }
    
    function getPinnedPost(int $user): ?Post
    {
        $post = (clone $this->posts)->where([
            "wall"    => $user,
            "pinned"  => true,
            "deleted" => false,
        ])->fetch();
        
        return $this->toPost($post);
    }
    
    function getPostsFromUsersWall(int $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset    = $perPage * ($page - 1);
        
        $pinPost = $this->getPinnedPost($user);
        if(!is_null($pinPost)) {
            if($page === 1) {
                $perPage--;
                
                yield $pinPost;
            } else {
                $offset--;
            }
        }
        
        $sel = $this->posts->where([
            "wall"    => $user,
            "pinned"  => false,
            "deleted" => false,
        ])->order("created DESC")->limit($perPage, $offset);
        
        foreach($sel as $post)
            yield new Post($post);
    }
    
    function getPostsByHashtag(string $hashtag, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $hashtag = "#$hashtag";
        $sel = $this->posts
                    ->where("content LIKE ?", "%$hashtag%")
                    ->where("deleted", 0)
                    ->order("created DESC")
                    ->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        
        foreach($sel as $post)
            yield new Post($post);
    }
    
    function getPostCountByHashtag(string $hashtag): int
    {
        $hashtag = "#$hashtag";
        $sel = $this->posts
                    ->where("content LIKE ?", "%$hashtag%")
                    ->where("deleted", 0);
        
        return sizeof($sel);
    }
    
    function getPostById(int $wall, int $post): ?Post
    {
        $post = $this->posts->where(['wall' => $wall, 'virtual_id' => $post])->fetch();
        if(!is_null($post))
        
            return new Post($post);
        else
            return null;
        
    }
    
    function getPostCountOnUserWall(int $user): int
    {
        return sizeof($this->posts->where(["wall" => $user, "deleted" => 0]));
    }
}
