<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Entities\User;
use openvk\VKAPI\Handlers\Wall;

final class Newsfeed extends VKAPIRequestHandler
{
    public function get(string $fields = "", int $start_from = 0, int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 0, int $forGodSakePleaseDoNotReportAboutMyOnlineActivity = 0)
    {
        $this->requireUser();

        if ($forGodSakePleaseDoNotReportAboutMyOnlineActivity == 0) {
            $this->getUser()->updOnline($this->getPlatform());
        }

        $id    = $this->getUser()->getId();
        $subs  = DatabaseConnection::i()
                    ->getContext()
                    ->table("subscriptions")
                    ->where("follower", $id);
        $ids   = array_map(function ($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));
        $ids[] = $this->getUser()->getId();

        $posts = DatabaseConnection::i()
                    ->getContext()
                    ->table("posts")
                    ->select("id")
                    ->where("wall IN (?)", $ids)
                    ->where("deleted", 0)
                    ->where("suggested", 0)
                    ->where("id < (?)", empty($start_from) ? PHP_INT_MAX : $start_from)
                    ->where("? <= created", empty($start_time) ? 0 : $start_time)
                    ->where("? >= created", empty($end_time) ? PHP_INT_MAX : $end_time)
                    ->order("created DESC");

        $rposts = [];
        foreach ($posts->page((int) ($offset + 1), $count) as $post) {
            $rposts[] = (new PostsRepo())->get($post->id)->getPrettyId();
        }

        $response = (new Wall())->getById(implode(',', $rposts), $extended, $fields, $this->getUser());
        $response->next_from = end(end($posts->page((int) ($offset + 1), $count))); // ну и костыли пиздец конечно)

        return $response;
    }

    public function getGlobal(string $fields = "", int $start_from = 0, int $start_time = 0, int $end_time = 0, int $offset = 0, int $count = 30, int $extended = 0, int $rss = 0, int $return_banned = 0)
    {
        $this->requireUser();

        $queryBase = "FROM `posts` LEFT JOIN `groups` ON GREATEST(`posts`.`wall`, 0) = 0 AND `groups`.`id` = ABS(`posts`.`wall`) LEFT JOIN `profiles` ON LEAST(`posts`.`wall`, 0) = 0 AND `profiles`.`id` = ABS(`posts`.`wall`)";
        $queryBase .= "WHERE (`groups`.`hide_from_global_feed` = 0 OR `groups`.`name` IS NULL) AND (`profiles`.`profile_type` = 0 OR `profiles`.`first_name` IS NULL) AND `posts`.`deleted` = 0 AND `posts`.`suggested` = 0";

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

        $start_from = empty($start_from) ? PHP_INT_MAX : $start_from;
        $start_time = empty($start_time) ? 0 : $start_time;
        $end_time = empty($end_time) ? PHP_INT_MAX : $end_time;
        $posts = DatabaseConnection::i()->getConnection()->query("SELECT `posts`.`id` " . $queryBase . " AND `posts`.`id` <= " . $start_from . " AND " . $start_time . " <= `posts`.`created` AND `posts`.`created` <= " . $end_time . " ORDER BY `created` DESC LIMIT " . $count . " OFFSET " . $offset);

        $rposts = [];
        $ids = [];
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
            $ids[] = $post->id;
        }

        $response = (new Wall())->getById(implode(',', $rposts), $extended, $fields, $this->getUser());
        $response->next_from = end($ids);

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
