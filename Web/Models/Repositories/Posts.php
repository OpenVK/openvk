<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Posts
{
    private $context;
    private $posts;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->posts   = $this->context->table("posts");
    }

    private function toPost(?ActiveRow $ar): ?Post
    {
        return is_null($ar) ? null : new Post($ar);
    }

    public function get(int $id): ?Post
    {
        return $this->toPost($this->posts->get($id));
    }

    public function getPinnedPost(int $user): ?Post
    {
        $post = (clone $this->posts)->where([
            "wall"    => $user,
            "pinned"  => true,
            "deleted" => false,
        ])->fetch();

        return $this->toPost($post);
    }

    public function getPostsFromUsersWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $pinPost = $this->getPinnedPost($user);
        if (is_null($offset) || $offset == 0) {
            if (!is_null($pinPost)) {
                if ($page === 1) {
                    $perPage--;

                    yield $pinPost;
                } else {
                    $offset--;
                }
            }
        } elseif (!is_null($offset) && $pinPost) {
            $offset--;
        }

        $sel = $this->posts->where([
            "wall"      => $user,
            "pinned"    => false,
            "deleted"   => false,
            "suggested" => 0,
        ])->order("created DESC")->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getOwnersPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $sel = $this->posts->where([
            "wall"      => $user,
            "deleted"   => false,
            "suggested" => 0,
        ]);

        if ($user > 0) {
            $sel->where("owner", $user);
        } else {
            $sel->where("flags !=", 0);
        }

        $sel->order("created DESC")->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getOthersPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $sel = $this->posts->where([
            "wall"      => $user,
            "deleted"   => false,
            "suggested" => 0,
        ]);

        if ($user > 0) {
            $sel->where("owner !=", $user);
        } else {
            $sel->where("flags", 0);
        }

        $sel->order("created DESC")->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getPostsByHashtag(string $hashtag, int $page = 1, ?int $perPage = null): \Traversable
    {
        $hashtag = "#$hashtag";
        $sel = $this->posts
                    ->where("MATCH (content) AGAINST (? IN BOOLEAN MODE)", "+$hashtag")
                    ->where("deleted", 0)
                    ->order("created DESC")
                    ->where("suggested", 0)
                    ->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getPostCountByHashtag(string $hashtag): int
    {
        $hashtag = "#$hashtag";
        $sel = $this->posts
                    ->where("content LIKE ?", "%$hashtag%")
                    ->where("deleted", 0)
                    ->where("suggested", 0);

        return sizeof($sel);
    }

    public function getPostById(int $wall, int $post, bool $forceSuggestion = false): ?Post
    {
        $post = $this->posts->where(['wall' => $wall, 'virtual_id' => $post]);

        if (!$forceSuggestion) {
            $post->where("suggested", 0);
        }

        $post = $post->fetch();

        if (!is_null($post)) {
            return new Post($post);
        } else {
            return null;
        }

    }

    public function find(string $query = "", array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $query = "%$query%";
        $result = $this->posts->where("content LIKE ?", $query)->where("deleted", 0)->where("suggested", 0);
        $order_str = 'id';

        switch ($order['type']) {
            case 'id':
                $order_str = 'created ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        foreach ($params as $paramName => $paramValue) {
            if (is_null($paramValue) || $paramValue == '') {
                continue;
            }

            switch ($paramName) {
                case "before":
                    $result->where("created < ?", $paramValue);
                    break;
                case "after":
                    $result->where("created > ?", $paramValue);
                    break;
                    /*case 'die_in_agony':
                        $result->where("nsfw", 1);
                        break;
                    case 'ads':
                        $result->where("ad", 1);
                        break;*/
                    # БУДЬ МАКСИМАЛЬНО АККУРАТЕН С ДАННЫМ ПАРАМЕТРОМ
                case 'from_me':
                    $result->where("owner", $paramValue);
                    break;
                case 'wall_id':
                    $result->where("wall", $paramValue);
                    break;
            }
        }

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Post", $result);
    }

    public function getPostCountOnUserWall(int $user): int
    {
        return sizeof($this->posts->where(["wall" => $user, "deleted" => 0, "suggested" => 0]));
    }

    public function getOwnersCountOnUserWall(int $user): int
    {
        if ($user > 0) {
            return sizeof($this->posts->where(["wall" => $user, "deleted" => 0, "owner" => $user]));
        } else {
            return sizeof($this->posts->where(["wall" => $user, "deleted" => 0, "suggested" => 0])->where("flags !=", 0));
        }
    }

    public function getOthersCountOnUserWall(int $user): int
    {
        if ($user > 0) {
            return sizeof($this->posts->where(["wall" => $user, "deleted" => 0])->where("owner !=", $user));
        } else {
            return sizeof($this->posts->where(["wall" => $user, "deleted" => 0, "suggested" => 0])->where("flags", 0));
        }
    }

    public function getSuggestedPosts(int $club, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $sel = $this->posts
                    ->where("deleted", 0)
                    ->where("wall", $club * -1)
                    ->order("created DESC")
                    ->where("suggested", 1)
                    ->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getSuggestedPostsCount(int $club)
    {
        return sizeof($this->posts->where(["wall" => $club * -1, "deleted" => 0, "suggested" => 1]));
    }

    public function getSuggestedPostsByUser(int $club, int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $sel = $this->posts
                    ->where("deleted", 0)
                    ->where("wall", $club * -1)
                    ->where("owner", $user)
                    ->order("created DESC")
                    ->where("suggested", 1)
                    ->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getSuggestedPostsCountByUser(int $club, int $user): int
    {
        return sizeof($this->posts->where(["wall" => $club * -1, "deleted" => 0, "suggested" => 1, "owner" => $user]));
    }

    public function getCount(): int
    {
        return (clone $this->posts)->count('*');
    }
}
