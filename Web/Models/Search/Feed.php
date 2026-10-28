<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\Selection;
use openvk\Web\Models\Repositories\{Users, Clubs, Posts};
use openvk\Web\Models\Entities\User;
use openvk\Web\Util\Cache;
use Bhaktaraz\RSSGenerator\Feed as RSSFeed;
use Bhaktaraz\RSSGenerator\Item;
use Bhaktaraz\RSSGenerator\Channel;

class Feed
{
    private $owner;
    private $global;
    private $wall;
    private $tmp_queryBase;

    public function __construct(User $owner, bool $global = false, $wall = null)
    {
        $this->owner = $owner;
        $this->global = $global;
        $this->wall = $wall;
        $this->tmp_queryBase = null;
    }

    public static function get(int $owner_id, bool $global = false)
    {
        $owner = get_entity_by_id($owner_id);

        if (!$owner) {
            throw new \Error("Not found local feed owner");
        }

        return new Feed($owner, $global);
    }

    private function checkPrivacy($user, string $filter = "post")
    {
        switch ($filter) {
            case "post":
                return true;
            case "photo":
                return $user->getPrivacyPermission("photos.read", $this->owner);
            case "video":
                return $user->getPrivacyPermission("videos.read", $this->owner);
            case "audio":
                return $user->getPrivacyPermission("audios.read", $this->owner);
            case "note":
                return $user->getPrivacyPermission("notes.read", $this->owner);
        }

        return true;
    }

    protected function getSubsIds(bool $keep_ignored = true, $list = null, string $filter = "post")
    {
        $ownerId = $this->owner->getRealId();
        $listId  = $list ? $list->getId() : 0;
        $cacheKey = "feed_subs_ids:{$ownerId}:{$listId}:{$filter}:" . ($keep_ignored ? '1' : '0');
        $cached = \openvk\Web\Util\Cache::remember($cacheKey, 600, function () use ($keep_ignored, $list, $filter) {
            $ids = [];

            if ($list != null) {
                foreach ($list->fetch($this->owner, 1, 10000, false, [5, 18]) as $item) {
                    if ($this->checkPrivacy($item, $filter)) {
                        $ids[] = $item->getRealId();
                    }
                }
            } else {
                $subs = DatabaseConnection::i()
                    ->getContext()
                    ->table("subscriptions")
                    ->where("follower", $this->owner->getRealId());
                $ids  = array_map(function ($rel) {
                    return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
                }, iterator_to_array($subs));
            }

            if (!$keep_ignored) {
                $ignored_sources_ids = $this->owner->getIgnoredSources(
                    0,
                    OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50,
                    true
                );
                $ids = array_diff($ids, $ignored_sources_ids);
            }

            if (!$list) {
                $ids[] = $this->owner->getRealId();
            }

            // too many sql requests...
            $entities = get_entities($ids);
            $cIds = [];

            foreach ($entities as $entry) {
                if (!$entry) {
                    continue;
                }

                if ($this->checkPrivacy($entry, $filter)) {
                    $cIds[] = $entry->getRealId();
                }
            }

            return $cIds;
        });

        return $cached;
    }

    public function getGlobalFeedCountCached(?string $queryBase = null): int
    {
        if ($queryBase) {
            $this->tmp_queryBase = $queryBase;
        }

        return Cache::remember(
            "feedcount:" . md5($this->tmp_queryBase),
            300,
            fn() => DatabaseConnection::i()->getConnection()->query("SELECT COUNT(*) " . $queryBase)->fetch()->{"COUNT(*)"}
        );
    }

    public function fetchFeed(bool $keep_ignored = true, bool $alien_posts = true, bool $keep_nsfw_settings = true, string $filter = "post", ?array $cursor = null, $list = null): Selection
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

            if ($keep_nsfw_settings === true && $this->owner->getNsfwTolerance() === User::NSFW_INTOLERANT) {
                $queryBase .= " AND `nsfw` = 0";
            }

            if (!$keep_ignored) {
                $ignored_sources_ids = $this->owner->getIgnoredSources(0, OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50, true);

                if (sizeof($ignored_sources_ids) > 0) {
                    $imploded_ids = implode("', '", $ignored_sources_ids);
                    $queryBase .= " AND `posts`.`wall` NOT IN ('$imploded_ids')";
                }
            }

            if ($cursorTime != null) {
                $cursorFilter = " AND (`posts`.`created` < {$cursorTime} OR (`posts`.`created` = {$cursorTime} AND `posts`.`id` < {$cursorId}))";
            }

            $this->getGlobalFeedCountCached($queryBase);

            $startFromFilter = "";
            if ($start_time != null) {
                $startFromFilter = " AND " . (int) $start_time . " <= `posts`.`created` AND `posts`.`created` <= " . (int) $end_time;
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
            $ids = $this->getSubsIds($keep_ignored, $list, $filter);

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

                if ($keep_nsfw_settings === true && $this->owner->getNsfwTolerance() === User::NSFW_INTOLERANT) {
                    $payload->where("nsfw", 0);
                }
            }
            if (!$list && $filter === "photo") {
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
            if (!$list && $filter === "video") {
                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("videos")
                    // ->select("id, created, owner")
                    ->where("owner IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("unlisted", 0)
                    ->order("created DESC, id DESC");
            }
            if (!$list && $filter === "note") {
                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("notes")
                    // ->select("id, created, owner")
                    ->where("owner IN (?)", $ids)
                    ->where("deleted", 0)
                    ->order("created DESC, id DESC");
            }
            if (!$list && $filter === "audio") {
                if (empty($ids)) {
                    $payload = DatabaseConnection::i()->getContext()->table("audios")->where("id", 0);
                    return $payload;
                }

                $ownerId = $this->owner->getRealId();
                $cacheKey = "feed_audio_ids:{$ownerId}:" . md5(implode(",", $ids));
                $audioIds = \openvk\Web\Util\Cache::remember($cacheKey, 600, function () use ($ids) {
                    $relations = DatabaseConnection::i()
                        ->getContext()
                        ->table("audio_relations")
                        ->select("entity, audio, index")
                        ->where("entity IN (?)", $ids)
                        ->order("index DESC")
                        ->limit(500);

                    $audioIds = [];

                    foreach ($relations as $item) {
                        $audioIds[] = (int) $item->audio;
                    }

                    return array_values(array_unique($audioIds));
                });

                if (empty($audioIds)) {
                    $payload = DatabaseConnection::i()->getContext()->table("audios")->where("id", 0);
                    return $payload;
                }

                $payload = DatabaseConnection::i()
                    ->getContext()
                    ->table("audios")
                    ->where("id IN (?)", $audioIds)
                    ->where("deleted", 0)
                    ->where("unlisted", 0)
                    ->order("FIELD(id, " . implode(",", $audioIds) . ")");
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
            ->where("deleted", 0)
            ->order("id DESC");

        return $comments;
    }
}
