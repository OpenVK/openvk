<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Entities\User;
use openvk\VKAPI\Handlers\Wall;

final class Newsfeed extends VKAPIRequestHandler
{
    private function parseCursor(?string $start_from): array
    {
        if (!empty($start_from) && strpos($start_from, '_') !== false) {
            $parts = explode('_', $start_from);
            return [(int) $parts[0], (int) $parts[1]];
        }

        return [PHP_INT_MAX, PHP_INT_MAX];
    }

    public function get(string $fields = "", string $start_from = "", int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 1, int $forGodSakePleaseDoNotReportAboutMyOnlineActivity = 0, int $with_alien_wall_posts = 0, string $filters = 'post')
    {
        $this->requireUser();

        if ($forGodSakePleaseDoNotReportAboutMyOnlineActivity == 0 || VKAPI_DECL_VER_MAJOR <= 5 && VKAPI_DECL_VER_MINOR <= 63) {
            $this->getUser()->updOnline($this->getPlatform());
        }

        $count  = max(1, min(100, $count));
        $offset = max(0, min(1000, $offset));

        [$cursorTime, $cursorId] = $this->parseCursor($start_from);

        $id    = $this->getUser()->getId();
        $subs  = DatabaseConnection::i()
                    ->getContext()
                    ->table("subscriptions")
                    ->where("follower", $id);
        $ids   = array_map(function ($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));
        $ids[] = $this->getUser()->getId();

        $filters   = array_unique(explode(',', $filters));
        $fetchCap  = $offset + $count;
        $startTime = empty($start_time) ? 0 : $start_time;
        $endTime   = empty($end_time) ? PHP_INT_MAX : $end_time;

        # cheap rows only — full structs are hydrated after paging, not before
        $candidates = [];

        if (in_array('post', $filters)) {
            $posts = DatabaseConnection::i()
                    ->getContext()
                    ->table("posts")
                    ->select("id, created")
                    ->where("wall IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("suggested", 0)
                    ->where("archived", 0)
                    ->where("created < ? OR (created = ? AND id < ?)", $cursorTime, $cursorTime, $cursorId)
                    ->where("? <= created", $startTime)
                    ->where("? >= created", $endTime)
                    ->order("created DESC, id DESC");

            if ($with_alien_wall_posts == 0) {
                $posts->where("(`posts`.`wall` < 0 AND (`posts`.`flags` & 128) > 0) OR (`posts`.`wall` > 0 AND `posts`.`wall` = `posts`.`owner`)");
            }

            foreach ($posts->limit($fetchCap) as $post) {
                $candidates[] = ['type' => 'post', 'id' => $post->id, 'created' => $post->created];
            }
        }

        if (in_array('photo', $filters)) {
            # deeper cap: grouping below can collapse many rows into one item
            $photoFetchCap = min(500, $fetchCap * 5);

            $photos = DatabaseConnection::i()
                    ->getContext()
                    ->table("photos")
                    ->select("id, created, owner")
                    ->where("owner IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("system", 0)
                    ->where("private", 0)
                    ->where("created < ? OR (created = ? AND id < ?)", $cursorTime, $cursorTime, $cursorId)
                    ->where("? <= created", $startTime)
                    ->where("? >= created", $endTime)
                    ->where("EXISTS (SELECT 1 FROM `album_relations` `ar` INNER JOIN `albums` `al` ON `al`.`id` = `ar`.`collection` WHERE `ar`.`media` = `photos`.`id` AND `al`.`special_type` = 0)")
                    ->order("created DESC, id DESC");

            # grouped by (source, UTC day): flat items don't render in real VK
            # clients (Kate Mobile confirmed) — they require photos:{count,items}.
            # groups only see this page's fetched rows, so a burst spanning a page
            # boundary can split into two groups — traded off against a full
            # server-side aggregate query.
            $photoGroups = [];
            $groupOrder  = [];
            foreach ($photos->limit($photoFetchCap) as $photo) {
                $owner = (int) $photo->owner;
                $day   = intdiv((int) $photo->created, 86400);
                $key   = "{$owner}_{$day}";

                if (!isset($photoGroups[$key])) {
                    $photoGroups[$key] = [
                        'type'          => 'photo',
                        'owner'         => $owner,
                        'created'       => $photo->created,
                        'id'            => $photo->id,
                        'cursorCreated' => $photo->created,
                        'cursorId'      => $photo->id,
                        'memberIds'     => [],
                    ];
                    $groupOrder[] = $key;
                }

                $photoGroups[$key]['memberIds'][]     = $photo->id;
                $photoGroups[$key]['cursorCreated']   = $photo->created;
                $photoGroups[$key]['cursorId']        = $photo->id;
            }

            foreach ($groupOrder as $key) {
                $candidates[] = $photoGroups[$key];
            }
        }

        if (in_array('video', $filters)) {
            $videos = DatabaseConnection::i()
                    ->getContext()
                    ->table("videos")
                    ->select("id, created, owner")
                    ->where("owner IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("created < ? OR (created = ? AND id < ?)", $cursorTime, $cursorTime, $cursorId)
                    ->where("? <= created", $startTime)
                    ->where("? >= created", $endTime)
                    ->order("created DESC, id DESC");

            foreach ($videos->limit($fetchCap) as $video) {
                $candidates[] = ['type' => 'video', 'id' => $video->id, 'created' => $video->created, 'owner' => (int) $video->owner];
            }
        }

        usort($candidates, function ($a, $b) {
            if ($a['created'] !== $b['created']) {
                return $b['created'] <=> $a['created'];
            }

            return $b['id'] <=> $a['id'];
        });

        $page = array_slice($candidates, $offset, $count);

        $next_from = null;
        if (!empty($page)) {
            $lastTuple = end($page);
            # anchor to the group's oldest member, not its display date, or the
            # next page re-fetches/re-groups it
            $cursorCreated = $lastTuple['cursorCreated'] ?? $lastTuple['created'];
            $cursorId      = $lastTuple['cursorId'] ?? $lastTuple['id'];
            $next_from     = "{$cursorCreated}_{$cursorId}";
        }

        $final_profiles = [];
        $final_groups   = [];

        # one at a time — a dropped post would desync a bulk call from the merged page order
        $hydratedPosts = [];
        foreach ($page as $tuple) {
            if ($tuple['type'] !== 'post') {
                continue;
            }

            $post = (new PostsRepo())->get($tuple['id']);
            if (!$post) {
                continue;
            }

            $response = (new Wall())->getById($post->getPrettyId(), $extended, $fields, $this->getUser());
            $items    = is_object($response) && isset($response->items) ? $response->items : (is_array($response) ? $response : []);
            if (empty($items)) {
                continue;
            }

            $item = $items[0];
            $item->type      = "post";
            $item->source_id = $item->owner_id;
            $hydratedPosts[$tuple['id']] = $item;

            if ($extended && is_object($response)) {
                foreach ($response->profiles ?? [] as $profile) {
                    $final_profiles[$profile->id] = $profile;
                }
                foreach ($response->groups ?? [] as $group) {
                    $final_groups[$group->id] = $group;
                }
            }
        }

        # wrapped shape required by real clients — see grouping note above
        $hydratedPhotos = [];
        $mediaOwnerIds  = [];
        foreach ($page as $tuple) {
            if ($tuple['type'] !== 'photo') {
                continue;
            }

            $visibleMembers = [];
            foreach ($tuple['memberIds'] as $memberId) {
                $photo = (new PhotosRepo())->get($memberId);
                if (!$photo || $photo->isDeleted() || !$photo->canBeViewedBy($this->getUser())) {
                    continue;
                }

                $visibleMembers[] = $photo;
            }

            if (empty($visibleMembers)) {
                continue;
            }

            $memberStructs = [];
            foreach (array_slice($visibleMembers, 0, 5) as $photo) {
                $memberStructs[] = $photo->toVkApiStruct(true, (bool) $extended);
            }

            $struct = (object) [
                'type'      => 'photo',
                'source_id' => $tuple['owner'],
                'date'      => $tuple['created'],
                'post_id'   => 0,
                'photos'    => (object) [
                    'count' => sizeof($visibleMembers),
                    'items' => $memberStructs,
                ],
            ];
            $hydratedPhotos[$tuple['id']] = $struct;
            $mediaOwnerIds[] = $tuple['owner'];
        }

        $hydratedVideos = [];
        foreach ($page as $tuple) {
            if ($tuple['type'] !== 'video') {
                continue;
            }

            $video = (new VideosRepo())->get($tuple['id']);
            if (!$video || $video->isDeleted() || !$video->canBeViewedBy($this->getUser())) {
                continue;
            }

            $struct = (object) $video->getApiStructure($this->getUser())->video;
            $struct->type      = "video";
            $struct->source_id = $tuple['owner'];
            $hydratedVideos[$tuple['id']] = $struct;
            $mediaOwnerIds[] = $tuple['owner'];
        }

        if ($extended && !empty($mediaOwnerIds)) {
            foreach (array_unique($mediaOwnerIds) as $ownerId) {
                if ($ownerId > 0) {
                    if (isset($final_profiles[$ownerId])) {
                        continue;
                    }

                    $user = (new UsersRepo())->get($ownerId);
                    if ($user) {
                        $final_profiles[$ownerId] = $user->toVkApiStruct($this->getUser(), $fields);
                    }
                } else {
                    $gid = abs($ownerId);
                    if (isset($final_groups[$gid])) {
                        continue;
                    }

                    $club = (new ClubsRepo())->get($gid);
                    if ($club) {
                        $final_groups[$gid] = $club->toVkApiStruct($this->getUser(), $fields);
                    }
                }
            }
        }

        $final_items = [];
        foreach ($page as $tuple) {
            switch ($tuple['type']) {
                case 'post':
                    if (isset($hydratedPosts[$tuple['id']])) {
                        $final_items[] = $hydratedPosts[$tuple['id']];
                    }
                    break;
                case 'photo':
                    if (isset($hydratedPhotos[$tuple['id']])) {
                        $final_items[] = $hydratedPhotos[$tuple['id']];
                    }
                    break;
                case 'video':
                    if (isset($hydratedVideos[$tuple['id']])) {
                        $final_items[] = $hydratedVideos[$tuple['id']];
                    }
                    break;
            }
        }

        $result = ['items' => $final_items];

        if ($extended) {
            $result['profiles'] = array_values($final_profiles);
            $result['groups']   = array_values($final_groups);
        }

        if (!is_null($next_from)) {
            $result['next_from'] = $next_from;
        }

        return $result;
    }

    public function getGlobal(string $fields = "", string $start_from = "", int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 1, int $rss = 0, int $return_banned = 0, int $with_alien_wall_posts = 0)
    {
        $this->requireUser();

        [$cursorTime, $cursorId] = $this->parseCursor($start_from);

        $queryBase = "FROM `posts` LEFT JOIN `groups` ON GREATEST(`posts`.`wall`, 0) = 0 AND `groups`.`id` = ABS(`posts`.`wall`) LEFT JOIN `profiles` ON LEAST(`posts`.`wall`, 0) = 0 AND `profiles`.`id` = ABS(`posts`.`wall`)";
        $queryBase .= " WHERE (`groups`.`hide_from_global_feed` = 0 OR `groups`.`name` IS NULL) AND (`profiles`.`profile_type` = 0 OR `profiles`.`first_name` IS NULL) AND `posts`.`deleted` = 0 AND `posts`.`suggested` = 0 AND `posts`.`archived` = 0";

        if ($with_alien_wall_posts == 0) {
            $queryBase .= " AND ((`posts`.`wall` < 0 AND (`posts`.`flags` & 128) > 0) OR (`posts`.`wall` > 0 AND `posts`.`wall` = `posts`.`owner`))";
        }

        if ($this->getUser()->getNsfwTolerance() === User::NSFW_INTOLERANT) {
            $queryBase .= " AND `nsfw` = 0";
        }

        if ($return_banned == 0) {
            $ignored_sources_ids = $this->getUser()->getIgnoredSources(0, OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50, true);

            if (sizeof($ignored_sources_ids) > 0) {
                $imploded_ids = implode("', '", $ignored_sources_ids);
                $queryBase .= " AND `posts`.`wall` NOT IN ('$imploded_ids')";
            }
        }

        $start_time = empty($start_time) ? 0 : $start_time;
        $end_time = empty($end_time) ? PHP_INT_MAX : $end_time;

        $cursorFilter = " AND (`posts`.`created` < {$cursorTime} OR (`posts`.`created` = {$cursorTime} AND `posts`.`id` < {$cursorId}))";

        $posts = DatabaseConnection::i()->getConnection()->query(
            "SELECT `posts`.`id`, `posts`.`created` " . $queryBase .
            $cursorFilter .
            " AND " . $start_time . " <= `posts`.`created` AND `posts`.`created` <= " . $end_time .
            " ORDER BY `created` DESC, `id` DESC LIMIT " . $count . " OFFSET " . $offset
        );

        $rposts = [];
        $lastPost = null;
        if ($rss == 1) {
            $channel = new \Bhaktaraz\RSSGenerator\Channel();
            $channel->title("Global Feed — " . OPENVK_ROOT_CONF['openvk']['appearance']['name'])
            ->description('OVK Global feed')
            ->url(ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/feed/all");

            foreach ($posts as $item) {
                $post   = (new PostsRepo())->get($item->id);
                if (!$post || $post->isDeleted()) {
                    continue;
                }

                $output = $post->toRss();
                $output->appendTo($channel);
            }

            return $channel;
        }

        foreach ($posts as $post) {
            $rposts[] = (new PostsRepo())->get($post->id)->getPrettyId();
            $lastPost = $post;
        }

        $response = (new Wall())->getById(implode(',', $rposts), $extended, $fields, $this->getUser());

        if ($lastPost) {
            $response->next_from = "{$lastPost->created}_{$lastPost->id}";
        }

        foreach ($response->items as $post) {
            $post->type = "post";
            $post->source_id = $post->owner_id;
        }

        return $response;
    }

    public function getRecommended(string $fields = "", string $start_from = "", int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 1, int $rss = 0, int $return_banned = 0)
    {
        // getGlobal alias
        return $this->getGlobal($fields, $start_from, $start_time, $end_time, $offset, $count, $extended, $rss, $return_banned);
    }

    public function search(string $q = "", int $extended = 1, int $count = 30, int $start_time = 0, int $end_time = 0, string $start_from = "", string $fields = ""): object
    {
        [$cursorTime, $cursorId] = $this->parseCursor($start_from);

        $start_time = empty($start_time) ? 0 : $start_time;
        $end_time = empty($end_time) ? PHP_INT_MAX : $end_time;

        $postsRepo = new PostsRepo();

        $queryBase = DatabaseConnection::i()->getContext()
            ->table("posts")
            ->select("id, created")
            ->where("content LIKE ?", "%{$q}%")
            ->where("deleted", 0)
            ->where("suggested", 0)
            ->where("archived", 0)
            ->where("created <= ?", $cursorTime)
            ->where("created < ? OR id < ?", $cursorTime, $cursorId)
            ->where("? <= created", $start_time)
            ->where("? >= created", $end_time);

        if (!$this->userAuthorized() || $this->getUser()->getNsfwTolerance() === User::NSFW_INTOLERANT) {
            $queryBase->where("nsfw", 0);
        }

        $queryBase->order("created DESC, id DESC");

        $rposts = [];
        $lastPost = null;
        foreach ($queryBase->limit($count) as $post) {
            $rposts[] = $postsRepo->get($post->id)->getPrettyId();
            $lastPost = $post;
        }

        if (empty($rposts)) {
            return (object) [
                "count" => 0,
                "items" => [],
            ];
        }

        $response = (new Wall())->getById(implode(',', $rposts), $extended, $fields, $this->getUser());

        if ($lastPost) {
            $response->next_from = "{$lastPost->created}_{$lastPost->id}";
        }

        foreach ($response->items as $post) {
            $post->type = "post";
            $post->source_id = $post->owner_id;
        }

        return $response;
    }

    public function getByType(string $feed_type = 'top', string $fields = "", int $start_from = 0, int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 0, int $return_banned = 0)
    {
        $this->requireUser();

        switch ($feed_type) {
            case 'top':
                return $this->getGlobal($fields, $start_from, $start_time, $end_time, $offset, $count, $extended, $return_banned);
                break;
            default:
                return $this->get($fields, $start_from, $start_time, $end_time, $offset, $count, $extended);
                break;
        }
    }

    public function getBanned(int $extended = 0, string $fields = "", string $name_case = "nom", int $merge = 0): object
    {
        $this->requireUser();

        $offset = 0;
        $count  = OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50;
        $banned = $this->getUser()->getIgnoredSources($offset, $count, ($extended != 1));
        $return_object = (object) [
            'groups'  => [],
            'members' => [],
        ];

        if ($extended == 0) {
            foreach ($banned as $ban) {
                if ($ban > 0) {
                    $return_object->members[] = $ban;
                } else {
                    $return_object->groups[] = $ban;
                }
            }
        } else {
            if ($merge == 1) {
                $return_object = (object) [
                    'count'  => sizeof($banned),
                    'items'  => [],
                ];

                foreach ($banned as $ban) {
                    $return_object->items[] = $ban->toVkApiStruct($this->getUser(), $fields);
                }
            } else {
                $return_object = (object) [
                    'groups'   => [],
                    'profiles' => [],
                ];

                foreach ($banned as $ban) {
                    if ($ban->getRealId() > 0) {
                        $return_object->profiles[] = $ban->toVkApiStruct($this->getUser(), $fields);
                    } else {
                        $return_object->groups[]   = $ban->toVkApiStruct($this->getUser(), $fields);
                    }
                }
            }
        }

        return $return_object;
    }

    public function addBan(string $user_ids = "", string $group_ids = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        # Formatting input ids
        if (!empty($user_ids)) {
            $user_ids = array_map(function ($el) {
                return (int) $el;
            }, explode(',', $user_ids));
            $user_ids = array_unique($user_ids);
        } else {
            $user_ids = [];
        }

        if (!empty($group_ids)) {
            $group_ids = array_map(function ($el) {
                return abs((int) $el) * -1;
            }, explode(',', $group_ids));
            $group_ids = array_unique($group_ids);
        } else {
            $group_ids = [];
        }

        $ids = array_merge($user_ids, $group_ids);
        if (sizeof($ids) < 1) {
            return 0;
        }

        if (sizeof($ids) > 10) {
            $this->fail(-10, "Limit of 'ids' is 10");
        }

        $config_limit = OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50;
        $user_ignores = $this->getUser()->getIgnoredSourcesCount();
        if (($user_ignores + sizeof($ids)) > $config_limit) {
            $this->fail(-50, "Ignoring limit exceeded");
        }

        $entities = get_entities($ids);
        $successes = 0;
        foreach ($entities as $entity) {
            if (!$entity || $entity->getRealId() === $this->getUser()->getRealId() || $entity->isHideFromGlobalFeedEnabled() || $entity->isIgnoredBy($this->getUser())) {
                continue;
            }

            $entity->addIgnore($this->getUser());
            $successes += 1;
        }

        return 1;
    }

    public function deleteBan(string $user_ids = "", string $group_ids = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (!empty($user_ids)) {
            $user_ids = array_map(function ($el) {
                return (int) $el;
            }, explode(',', $user_ids));
            $user_ids = array_unique($user_ids);
        } else {
            $user_ids = [];
        }

        if (!empty($group_ids)) {
            $group_ids = array_map(function ($el) {
                return abs((int) $el) * -1;
            }, explode(',', $group_ids));
            $group_ids = array_unique($group_ids);
        } else {
            $group_ids = [];
        }

        $ids = array_merge($user_ids, $group_ids);
        if (sizeof($ids) < 1) {
            return 0;
        }

        if (sizeof($ids) > 10) {
            $this->fail(-10, "Limit of ids is 10");
        }

        $entities = get_entities($ids);
        $successes = 0;
        foreach ($entities as $entity) {
            if (!$entity || $entity->getRealId() === $this->getUser()->getRealId() || !$entity->isIgnoredBy($this->getUser())) {
                continue;
            }

            $entity->removeIgnore($this->getUser());
            $successes += 1;
        }

        return 1;
    }
}
