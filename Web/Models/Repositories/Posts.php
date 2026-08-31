<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\{ActiveRow, Selection};
use Chandler\Database\DatabaseConnection;

class Posts
{
    /* aggressive sql caching */
    private static $cache = [];

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

    private function getBaseSelection(): Selection
    {
        return (clone $this->posts)->where([
            "deleted"   => false,
            "suggested" => 0,
            "archived"  => false,
        ])->where("created <= ?", time());
    }

    private function getWallSelection(int $user): Selection
    {
        return $this->getBaseSelection()->where([
            "wall"      => $user,
        ]);
    }

    private function getOwnersWallSelection(int $user): Selection
    {
        $sel = $this->getWallSelection($user);
        if ($user > 0) {
            $sel->where("owner", $user);
        } else {
            $sel->where("flags !=", 0);
        }

        return $sel;
    }

    private function getOthersWallSelection(int $user): Selection
    {
        $sel = $this->getWallSelection($user);
        if ($user > 0) {
            $sel->where("owner !=", $user);
        } else {
            $sel->where("flags", 0);
        }

        return $sel;
    }

    private function getArchivedWallSelection(int $user, ?int $year = null): Selection
    {
        $sel = (clone $this->posts)->where([
            "wall"      => $user,
            "deleted"   => false,
            "suggested" => 0,
            "archived"  => true,
        ]);

        return $this->applyYearFilter($sel, $year);
    }

    private function getPlannedWallSelection(int $user): Selection
    {
        return (clone $this->posts)->where([
            "wall"      => $user,
            "deleted"   => false,
            "archived"  => false,
        ])->order("created ASC")->where("created > ?", time());
    }

    public function getFeedSelection(array $user_ids): Selection
    {
        return $this->getBaseSelection()->where("wall IN (?)", $user_ids);
    }

    public function getSearchSelection(string $query): Selection
    {
        return $this->getBaseSelection()->where("content LIKE ?", "%{$query}%");
    }

    public function getGlobalFeedQuery(
        User $user,
        bool $with_alien_wall_posts = false,
        bool $return_banned = false
    ): string {
        $time = time();

        $queryBase = "FROM `posts` LEFT JOIN `groups` ON GREATEST(`posts`.`wall`, 0) = 0 AND `groups`.`id` = ABS(`posts`.`wall`) LEFT JOIN `profiles` ON LEAST(`posts`.`wall`, 0) = 0 AND `profiles`.`id` = ABS(`posts`.`wall`)";
        $queryBase .= " WHERE (`groups`.`hide_from_global_feed` = 0 OR `groups`.`name` IS NULL) AND ((`profiles`.`profile_type` = 0 AND `profiles`.`hide_global_feed` = 0) OR `profiles`.`first_name` IS NULL) AND `posts`.`deleted` = 0 AND `posts`.`suggested` = 0 AND `posts`.`archived` = 0 and `posts`.`created` <= $time";

        if (!$with_alien_wall_posts) {
            $queryBase .= " AND ((`posts`.`wall` < 0 AND (`posts`.`flags` & 128) > 0) OR (`posts`.`wall` > 0 AND `posts`.`wall` = `posts`.`owner`))";
        }

        if ($user->getNsfwTolerance() === User::NSFW_INTOLERANT) {
            $queryBase .= " AND `nsfw` = 0";
        }

        if (!$return_banned) {
            $ignored_sources_ids = $user->getIgnoredSources(0, OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50, true);

            if (sizeof($ignored_sources_ids) > 0) {
                $imploded_ids = implode("', '", $ignored_sources_ids);
                $queryBase .= " AND `posts`.`wall` NOT IN ('$imploded_ids')";
            }
        }
        return $queryBase;
    }

    public function get(int $id): ?Post
    {
        return self::$cache[$id] ??= $this->toPost($this->posts->get($id));
    }

    public function getPinnedPost(int $user): ?Post
    {
        $post = (clone $this->posts)->where([
            "wall"     => $user,
            "pinned"   => true,
            "deleted"  => false,
            "archived" => false,
        ])->fetch();

        return $this->toPost($post);
    }

    private function listPosts(Selection $sel, int $perPage, int $offset, ?string $order = "created DESC"): \Traversable
    {
        if (!is_null($order)) {
            $sel = $sel->order($order);
        }

        $sel = $sel->limit($perPage, $offset);
        foreach ($sel as $post) {
            yield $post->id => new Post($post);
        }
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

                    yield $pinPost->getId() => $pinPost;
                } else {
                    $offset--;
                }
            }
        } elseif (!is_null($offset) && $pinPost) {
            $offset--;
        }

        yield from $this->listPosts($this->getWallSelection($user)->where(["pinned" => false]), $perPage, $offset);
    }

    public function getOwnersPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        yield from $this->listPosts($this->getOwnersWallSelection($user), $perPage, $offset);
    }

    public function getOthersPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        yield from $this->listPosts($this->getOthersWallSelection($user), $perPage, $offset);
    }

    public function getPlannedPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        yield from $this->listPosts($this->getPlannedWallSelection($user), $perPage, $offset, null);
    }

    public function getAllPlannedPostsFromWall(int $user): \Traversable
    {
        $sel = $this->getPlannedWallSelection($user);
        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getPostsByHashtag(string $hashtag, int $page = 1, ?int $perPage = null): \Traversable
    {
        $hashtag = "#$hashtag";
        $sel = $this->getBaseSelection()
                    ->order("created DESC")
                    ->where("MATCH (content) AGAINST (? IN BOOLEAN MODE)", "+$hashtag")
                    ->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getPostCountByHashtag(string $hashtag): int
    {
        $hashtag = "#$hashtag";
        $sel = $this->getBaseSelection()->where("MATCH (content) AGAINST (? IN BOOLEAN MODE)", "+$hashtag");

        return sizeof($sel);
    }

    public function getPostById(int $wall, int $post, bool $forceSuggestion = false, bool $showPlanned = false): ?Post
    {
        $post = $this->posts->where(['wall' => $wall, 'virtual_id' => $post]);

        if (!$forceSuggestion) {
            $post->where("suggested", 0);
        }

        if (!$showPlanned) {
            $post->where("created <= ?", time());
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
        $result = $this->getSearchSelection($query);
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
                case "ignore_private":
                    if (!$paramValue) {
                        break;
                    }

                    # Only public walls with existing owners (search must not 500 on deleted entities)
                    $openUsers = $this->context->table("profiles")
                        ->select("id")
                        ->where(["deleted" => 0, "profile_type" => 0]);
                    $publicGroups = $this->context->table("groups")
                        ->select("id")
                        ->where("hide_from_global_feed", 0);
                    $result->where(
                        "(wall > 0 AND wall IN (?)) OR (wall < 0 AND (-wall) IN (?))",
                        $openUsers,
                        $publicGroups
                    );
                    $result->where(
                        "owner IN (?)",
                        $this->context->table("profiles")->select("id")->where("deleted", 0)
                    );
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
        return sizeof($this->getWallSelection($user));
    }

    public function getOwnersCountOnUserWall(int $user): int
    {
        return sizeof($this->getOwnersWallSelection($user));
    }

    public function getOthersCountOnUserWall(int $user): int
    {
        return sizeof($this->getOthersWallSelection($user));
    }

    public function getPlannedCountOnUserWall(int $user): int
    {
        return sizeof($this->getPlannedWallSelection($user));
    }

    private function applyYearFilter(Selection $selection, ?int $year): Selection
    {
        if (!is_null($year)) {
            $selection->where("created >= ? AND created < ?", mktime(0, 0, 0, 1, 1, $year), mktime(0, 0, 0, 1, 1, $year + 1));
        }

        return $selection;
    }

    public function getPostYearsOnWall(int $user): array
    {
        $years = [];
        $posts = (clone $this->posts)->select("created")->where([
            "wall"      => $user,
            "deleted"   => false,
            "suggested" => 0,
        ])->where("created <= ?", time());

        foreach ($posts as $post) {
            $years[(int) date("Y", $post->created)] = true;
        }

        $years = array_keys($years);
        rsort($years, SORT_NUMERIC);

        return $years;
    }

    public function getArchivedPostsFromWall(int $user, int $page = 1, ?int $perPage = null, ?int $offset = null, ?int $year = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset ??= $perPage * ($page - 1);

        $sel = $this->getArchivedWallSelection($user, $year)->order("created DESC")->limit($perPage, $offset);

        foreach ($sel as $post) {
            yield new Post($post);
        }
    }

    public function getArchivedCountOnUserWall(int $user, ?int $year = null): int
    {
        return sizeof($this->getArchivedWallSelection($user, $year));
    }

    public function setArchivedOnWall(int $user, bool $archived, ?int $year = null): int
    {
        $posts = (clone $this->posts)->where([
            "wall"      => $user,
            "deleted"   => false,
            "suggested" => 0,
        ])->where("created <= ?", time());
        $this->applyYearFilter($posts, $year);

        $changes = ["archived" => $archived];
        if ($archived) {
            $changes["pinned"] = false;
        }

        return $posts->update($changes);
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
