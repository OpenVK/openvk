<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\Selection;
use openvk\Web\Models\Repositories\{Users, Clubs, Posts};
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\RowModel;
use openvk\Web\Util\Cache;
use Bhaktaraz\RSSGenerator\Feed as RSSFeed;
use Bhaktaraz\RSSGenerator\Item;
use Bhaktaraz\RSSGenerator\Channel;

class Feed
{
    private $owner;
    private $global;
    private $wall;

    public function __construct(User $owner, bool $global = false, $wall = null)
    {
        $this->owner = $owner;
        $this->global = $global;
        $this->wall = $wall;
    }

    public static function get(int $owner_id, bool $global = false)
    {
        $owner = get_entity_by_id($owner_id);

        if (!$owner) {
            throw new \FatalError("Not found local feed owner");
        }

        return new Feed($owner, $global);
    }

    protected function getSubsIds(bool $keep_ignored = true)
    {
        $subs = DatabaseConnection::i()
                ->getContext()
                ->table("subscriptions")
                ->where("follower", $owner_id);
        $ids   = array_map(function ($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));

        if ($keep_ignored == false) {
            $ignored_sources_ids = $this->owner->getIgnoredSources(
                0,
                OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50,
                true
            );
            $ids = array_diff($ids, $ignored_sources_ids);
        }

        $ids[] = $this->owner->getRealId();

        return $ids;
    }

    public function fetchFeed(bool $keep_ignored = true, bool $alien_posts = true, bool $keep_nsfw_settings = true, string $filter = "post", ?array $cursor = null): Selection
    {
        $payload = null;

        if ($this->global == true) {
            [$cursorTime, $cursorId, $start_time, $end_time, $offset, $count] = $cursor;

            $cursorFilter = "";
            $queryBase = "FROM `posts` LEFT JOIN `groups` ON GREATEST(`posts`.`wall`, 0) = 0 AND `groups`.`id` = ABS(`posts`.`wall`) LEFT JOIN `profiles` ON LEAST(`posts`.`wall`, 0) = 0 AND `profiles`.`id` = ABS(`posts`.`wall`)";
            $queryBase .= " WHERE (`groups`.`hide_from_global_feed` = 0 OR `groups`.`name` IS NULL) AND (`profiles`.`profile_type` = 0 OR `profiles`.`first_name` IS NULL) AND `posts`.`deleted` = 0 AND `posts`.`suggested` = 0 AND `posts`.`archived` = 0";

            if ($alien_posts == false) {
                $queryBase .= " AND ((`posts`.`wall` < 0 AND (`posts`.`flags` & 128) > 0) OR (`posts`.`wall` > 0 AND `posts`.`wall` = `posts`.`owner`))";
            }

            if ($keepNsfwSettings === true && $this->owner->getNsfwTolerance() === User::NSFW_INTOLERANT) {
                $queryBase .= " AND `nsfw` = 0";
            }

            if ($keep_ignored == false) {
                $ignored_sources_ids = $this->owner->getIgnoredSources(0, OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50, true);

                if (sizeof($ignored_sources_ids) > 0) {
                    $imploded_ids = implode("', '", $ignored_sources_ids);
                    $queryBase .= " AND `posts`.`wall` NOT IN ('$imploded_ids')";
                }
            }

            if ($cursorTime != null) {
                $cursorFilter = " AND (`posts`.`created` < {$cursorTime} OR (`posts`.`created` = {$cursorTime} AND `posts`.`id` < {$cursorId}))";
            }

            $countCached = Cache::remember(
                "feedcount:" . md5($queryBase),
                300,
                fn() => DatabaseConnection::i()->getConnection()->query("SELECT COUNT(*) " . $queryBase)->fetch()->{"COUNT(*)"}
            );

            if ($start_time != null) {
                $startFromFilter = " AND " . $start_time . " <= `posts`.`created` AND `posts`.`created` <= " . $end_time;
            }

            $posts = DatabaseConnection::i()->getConnection()->query(
                "SELECT `posts`.`id`, `posts`.`created`, `posts`.`wall`, `posts`.`virtual_id` " . $queryBase .
                $cursorFilter .
                $startFromFilter .
                " ORDER BY `created` DESC, `id` DESC LIMIT " . $count . " OFFSET " . $offset
            );
            $ids = [];

            foreach ($posts as $item) {
                $ids[] = $item->id;
            }

            $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("posts")
                    ->where("id IN (?)", $ids)
                    ->order("`created` DESC, `id` DESC");
        } else {
            $ids = $this->getSubsIds($keep_ignored);

            if ($filter === "post") {
                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("posts")
                    // ->select("id, created")
                    ->where("wall IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("suggested", 0)
                    ->where("archived", 0)
                    ->order("created DESC, id DESC");

                if ($alien_posts === false) {
                    $payload->where("(`posts`.`wall` < 0 AND (`posts`.`flags` & 128) > 0) OR (`posts`.`wall` > 0 AND `posts`.`wall` = `posts`.`owner`)");
                }

                if ($keepNsfwSettings === true && $this->owner->getNsfwTolerance() === User::NSFW_INTOLERANT) {
                    $payload->where("nsfw", 0);
                }
            }
            if ($filter === "photo") {
                $payload = DatabaseConnection::i()
                        ->getContext()
                        ->table("photos")
                        // ->select("id, created, owner")
                        ->where("owner IN (?)", $ids)
                        ->where("deleted", 0)
                        ->where("system", 0)
                        ->where("private", 0)
                        ->where("unlisted", 0)
                        ->order("created DESC, id DESC");
            }
            if ($filter === "video") {
                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("videos")
                    // ->select("id, created, owner")
                    ->where("owner IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("unlisted", 0)
                    ->order("created DESC, id DESC");
            }
            if ($filter === "audio") {
                $relations = DatabaseConnection::i()
                    ->getContext()
                    ->table("audio_relations")
                    ->select("entity, audio, index")
                    ->where("entity IN (?)", $ids)
                    ->order("index DESC");

                $audioIds = [];

                foreach ($relations as $item) {
                    $audioIds[] = $item->audio;
                }

                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("audios")
                     // ->select("id, created, owner")
                    ->where("id IN (?)", $audioIds)
                    ->where("deleted", 0)
                    ->where("unlisted", 0)
                    ->order("created DESC, id DESC");
            }
        }

        return $payload;
    }

    public function fetchComments()
    {
        $comments = DatabaseConnection::i()
            ->getContext()
            ->table("comments")
            // ->select("target")
            // ->where("model", "openvk\\Web\\Models\\Entities\\Post")
            ->where("owner", $this->owner->getRealId())
            ->where("deleted", 0);

        return $comments;
    }
}
