<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Exceptions\TooMuchOptionsException;
use openvk\Web\Models\Entities\{Poll, Post, Photo, Video, Club, User};
use openvk\Web\Models\Entities\Notifications\{MentionNotification, RepostNotification, WallPostNotification, PostAcceptedNotification, NewSuggestedPostsNotification};
use openvk\Web\Models\Repositories\{Posts, Users, Clubs, Albums, Notes, Videos, Comments, Photos, Audios};
use Chandler\Database\DatabaseConnection;
use Nette\InvalidStateException as ISE;
use Bhaktaraz\RSSGenerator\Item;
use Bhaktaraz\RSSGenerator\Feed;
use Bhaktaraz\RSSGenerator\Channel;

final class WallPresenter extends OpenVKPresenter
{
    private $posts;

    public function __construct(Posts $posts)
    {
        $this->posts = $posts;

        parent::__construct();
    }

    private function logPostView(Post $post, int $wall): void
    {
        if (is_null($this->user)) {
            return;
        }

        $this->logEvent("postView", [
            "profile"    => $this->user->identity->getId(),
            "post"       => $post->getId(),
            "owner"      => abs($wall),
            "group"      => $wall < 0,
            "subscribed" => $wall < 0 ? $post->getOwner()->getSubscriptionStatus($this->user->identity) : false,
        ]);
    }

    private function logPostsViewed(array &$posts, int $wall): void
    {
        $x = array_values($posts); # clone array (otherwise Nette DB objects will become kinda gay)

        foreach ($x as $post) {
            $this->logPostView($post, $wall);
        }
    }

    public function renderWall(int $user, bool $embedded = false): void
    {
        $owner = ($user < 0 ? (new Clubs()) : (new Users()))->get(abs($user));
        if ($owner->isBanned() || !$owner->canBeViewedBy($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if ($user > 0 && $owner->isDeleted()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if (is_null($this->user)) {
            $canPost = false;
        } elseif ($user > 0) {
            $canPost = $owner->getPrivacyPermission("wall.write", $this->user->identity);
        } elseif ($user < 0) {
            if ($owner->canBeModifiedBy($this->user->identity)) {
                $canPost = true;
            } else {
                $canPost = $owner->canPost();
            }
        } else {
            $canPost = false;
        }

        if ($embedded == true) {
            $this->template->_template = "components/wall.latte";
        }
        $this->template->oObj = $owner;
        if ($user < 0) {
            $this->template->club = $owner;
        }

        $iterator = null;
        $count = 0;
        $type = $this->queryParam("type") ?? "all";

        switch ($type) {
            default:
            case "all":
                $iterator = $this->posts->getPostsFromUsersWall($user, (int) ($_GET["p"] ?? 1));
                $count = $this->posts->getPostCountOnUserWall($user);
                break;
            case "owners":
                $iterator = $this->posts->getOwnersPostsFromWall($user, (int) ($_GET["p"] ?? 1));
                $count = $this->posts->getOwnersCountOnUserWall($user);
                break;
            case "others":
                $iterator = $this->posts->getOthersPostsFromWall($user, (int) ($_GET["p"] ?? 1));
                $count = $this->posts->getOthersCountOnUserWall($user);
                break;
            case "search":
                $iterator = $this->posts->find($_GET["q"] ?? "", ["wall_id" => $user]);
                $count = $iterator->size();
                break;
        }

        $this->template->owner   = $user;
        $this->template->canPost = $canPost;
        $this->template->count   = $count;
        $this->template->type    = $type;
        $this->template->posts   = iterator_to_array($iterator);
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($this->template->posts),
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];


        $this->logPostsViewed($this->template->posts, $user);
    }

    public function renderWallEmbedded(int $user): void
    {
        $this->renderWall($user, true);
    }

    public function renderRSS(int $user): void
    {
        $owner = ($user < 0 ? (new Clubs()) : (new Users()))->get(abs($user));
        if (is_null($this->user)) {
            $canPost = false;
        } elseif ($user > 0) {
            if (!$owner->isBanned() && $owner->canBeViewedBy($this->user->identity)) {
                $canPost = $owner->getPrivacyPermission("wall.write", $this->user->identity);
            } else {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }
        } elseif ($user < 0) {
            if ($owner->canBeModifiedBy($this->user->identity)) {
                $canPost = true;
            } elseif ($owner->isBanned()) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            } else {
                $canPost = $owner->canPost();
            }
        } else {
            $canPost = false;
        }

        $posts = iterator_to_array($this->posts->getPostsFromUsersWall($user));

        $feed = new Feed();

        $channel = new Channel();
        $channel->title($owner->getCanonicalName() . " — " . OPENVK_ROOT_CONF['openvk']['appearance']['name'])->url(ovk_scheme(true) . $_SERVER["HTTP_HOST"])->appendTo($feed);

        foreach ($posts as $post) {
            $item = new Item();
            $item
                ->title($post->getOwner()->getCanonicalName())
                ->description($post->getText())
                ->url(ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/wall{$post->getPrettyId()}")
                ->pubDate($post->getPublicationTime()->timestamp())
                ->appendTo($channel);
        }

        header("Content-Type: application/rss+xml");
        exit($feed);
    }

    public function renderFeed(): void
    {
        $this->assertUserLoggedIn();

        $id    = $this->user->id;
        $subs  = DatabaseConnection::i()
                 ->getContext()
                 ->table("subscriptions")
                 ->where("follower", $id);
        $ids   = array_map(function ($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));
        $ids[] = $this->user->id;

        $perPage = min((int) ($_GET["posts"] ?? OPENVK_DEFAULT_PER_PAGE), 50);
        $posts   = DatabaseConnection::i()
                   ->getContext()
                   ->table("posts")
                   ->select("id")
                   ->where("wall IN (?)", $ids)
                   ->where("deleted", 0)
                   ->where("suggested", 0)
                   ->order("created DESC");
        $this->template->paginatorConf = (object) [
            "count"   => sizeof($posts),
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => $posts->page((int) ($_GET["p"] ?? 1), $perPage)->count(),
            "perPage" => $perPage,
        ];
        $this->template->posts = [];
        foreach ($posts->page((int) ($_GET["p"] ?? 1), $perPage) as $post) {
            $this->template->posts[] = $this->posts->get($post->id);
        }
    }

    public function renderGlobalFeed(): void
    {
        $this->assertUserLoggedIn();

        $page  = (int) ($_GET["p"] ?? 1);
        $pPage = min((int) ($_GET["posts"] ?? OPENVK_DEFAULT_PER_PAGE), 50);

        $queryBase = "FROM `posts` LEFT JOIN `groups` ON GREATEST(`posts`.`wall`, 0) = 0 AND `groups`.`id` = ABS(`posts`.`wall`) LEFT JOIN `profiles` ON LEAST(`posts`.`wall`, 0) = 0 AND `profiles`.`id` = ABS(`posts`.`wall`)";
        $queryBase .= "WHERE (`groups`.`hide_from_global_feed` = 0 OR `groups`.`name` IS NULL) AND ((`profiles`.`profile_type` = 0 AND `profiles`.`hide_global_feed` = 0) OR `profiles`.`first_name` IS NULL) AND `posts`.`deleted` = 0 AND `posts`.`suggested` = 0";

        if ($this->user->identity->getNsfwTolerance() === User::NSFW_INTOLERANT) {
            $queryBase .= " AND `nsfw` = 0";
        }

        if (((int) $this->queryParam('return_banned')) == 0) {
            $ignored_sources_ids = $this->user->identity->getIgnoredSources(0, OPENVK_ROOT_CONF['openvk']['preferences']['newsfeed']['ignoredSourcesLimit'] ?? 50, true);

            if (sizeof($ignored_sources_ids) > 0) {
                $imploded_ids = implode("', '", $ignored_sources_ids);

                $queryBase .= " AND `posts`.`wall` NOT IN ('$imploded_ids')";
            }
        }

        $posts = DatabaseConnection::i()->getConnection()->query("SELECT `posts`.`id` " . $queryBase . " ORDER BY `created` DESC LIMIT " . $pPage . " OFFSET " . ($page - 1) * $pPage);
        $count = DatabaseConnection::i()->getConnection()->query("SELECT COUNT(*) " . $queryBase)->fetch()->{"COUNT(*)"};

        $this->template->_template     = "Wall/Feed.latte";
        $this->template->globalFeed    = true;
        $this->template->paginatorConf = (object) [
            "count"   => $count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => $posts->getRowCount(),
            "perPage" => $pPage,
        ];
        foreach ($posts as $post) {
            $this->template->posts[] = $this->posts->get($post->id);
        }
    }

    public function renderHashtagFeed(string $hashtag): void
    {
        $hashtag = rawurldecode($hashtag);

        $page  = (int) ($_GET["p"] ?? 1);
        $posts = $this->posts->getPostsByHashtag($hashtag, $page);
        $count = $this->posts->getPostCountByHashtag($hashtag);

        $this->template->hashtag       = $hashtag;
        $this->template->posts         = $posts;
        $this->template->paginatorConf = (object) [
            "count"   => 0,
            "page"    => $page,
            "amount"  => $count,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
    }

    public function renderMakePost(int $wall): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $wallOwner = ($wall > 0 ? (new Users())->get($wall) : (new Clubs())->get($wall * -1));

        if ($wallOwner === null) {
            $this->flashFail("err", tr("failed_to_publish_post"), tr("error_4"));
        }

        if ($wallOwner->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if ($wall > 0) {
            $canPost = $wallOwner->getPrivacyPermission("wall.write", $this->user->identity);
        } elseif ($wall < 0) {
            if ($wallOwner->canBeModifiedBy($this->user->identity)) {
                $canPost = true;
            } else {
                $canPost = $wallOwner->canPost();
            }
        } else {
            $canPost = false;
        }

        if (!$canPost) {
            $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
        }

        $anon = OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["enable"];
        if ($wallOwner instanceof Club && $this->postParam("as_group") === "on" && $this->postParam("force_sign") !== "on" && $anon) {
            $manager = $wallOwner->getManager($this->user->identity);
            if ($manager) {
                $anon = $manager->isHidden();
            } elseif ($this->user->identity->getId() === $wallOwner->getOwner()->getId()) {
                $anon = $wallOwner->isOwnerHidden();
            }
        } else {
            $anon = $anon && $this->postParam("anon") === "on";
        }

        $flags = 0;
        if ($this->postParam("as_group") === "on" && $wallOwner instanceof Club && $wallOwner->canBeModifiedBy($this->user->identity)) {
            $flags |= 0b10000000;
        }
        if ($this->postParam("force_sign") === "on") {
            $flags |= 0b01000000;
        }

        $horizontal_attachments = [];
        $vertical_attachments = [];
        if (!empty($this->postParam("horizontal_attachments"))) {
            $horizontal_attachments_array = array_slice(explode(",", $this->postParam("horizontal_attachments")), 0, OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxAttachments"]);
            if (sizeof($horizontal_attachments_array) > 0) {
                $horizontal_attachments = parseAttachments($horizontal_attachments_array, ['photo', 'video']);
            }
        }

        if (!empty($this->postParam("vertical_attachments"))) {
            $vertical_attachments_array = array_slice(explode(",", $this->postParam("vertical_attachments")), 0, OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxAttachments"]);
            if (sizeof($vertical_attachments_array) > 0) {
                $vertical_attachments = parseAttachments($vertical_attachments_array, ['audio', 'note', 'doc']);
            }
        }

        try {
            $poll = null;
            $xml = $this->postParam("poll");
            if (!is_null($xml) && $xml != "none") {
                $poll = Poll::import($this->user->identity, $xml);
            }
        } catch (TooMuchOptionsException $e) {
            $this->flashFail("err", tr("failed_to_publish_post"), tr("poll_err_to_much_options"));
        } catch (\UnexpectedValueException $e) {
            $this->flashFail("err", tr("failed_to_publish_post"), "Poll format invalid");
        }

        $geo = null;

        if (!is_null($this->postParam("geo")) && $this->postParam("geo") != "") {
            $geo = json_decode($this->postParam("geo"), true, JSON_UNESCAPED_UNICODE);
            if ($geo["lat"] && $geo["lng"] && $geo["name"]) {
                $latitude = number_format((float) $geo["lat"], 8, ".", '');
                $longitude = number_format((float) $geo["lng"], 8, ".", '');
                if ($latitude > 90 || $latitude < -90 || $longitude > 180 || $longitude < -180) {
                    $this->flashFail("err", tr("error"), "Invalid latitude or longitude");
                }
            }
        }

        if (empty($this->postParam("text")) && sizeof($horizontal_attachments) < 1 && sizeof($vertical_attachments) < 1 && !$poll) {
            $this->flashFail("err", tr("failed_to_publish_post"), tr("post_is_empty_or_too_big"));
        }

        if (\openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->user->identity, "wall.post")) {
            $this->flashFail("err", tr("error"), tr("limit_exceed_exception"));
        }

        $should_be_suggested = $wall < 0 && !$wallOwner->canBeModifiedBy($this->user->identity) && $wallOwner->getWallType() == 2;
        try {
            $post = new Post();
            $post->setOwner($this->user->id);
            $post->setWall($wall);
            $post->setCreated(time());
            $post->setContent($this->postParam("text"));
            $post->setAnonymous($anon);
            $post->setFlags($flags);
            $post->setNsfw($this->postParam("nsfw") === "on");

            if (!empty($this->postParam("source")) && $this->postParam("source") != 'none') {
                try {
                    $post->setSource($this->postParam("source"));
                } catch (\Throwable) {
                }
            }

            if ($should_be_suggested) {
                $post->setSuggested(1);
            }

            if ($geo) {
                $post->setGeo($geo);
                $post->setGeo_Lat($latitude);
                $post->setGeo_Lon($longitude);
            }
            $post->save();
        } catch (\LengthException $ex) {
            $this->flashFail("err", tr("failed_to_publish_post"), tr("post_is_too_big"));
        }

        foreach ($horizontal_attachments as $horizontal_attachment) {
            if (!$horizontal_attachment || $horizontal_attachment->isDeleted() || !$horizontal_attachment->canBeViewedBy($this->user->identity)) {
                continue;
            }

            $post->attach($horizontal_attachment);
        }

        foreach ($vertical_attachments as $vertical_attachment) {
            if (!$vertical_attachment || $vertical_attachment->isDeleted() || !$vertical_attachment->canBeViewedBy($this->user->identity)) {
                continue;
            }

            $post->attach($vertical_attachment);
        }

        if (!is_null($poll)) {
            $post->attach($poll);
        }

        if ($wall > 0 && $wall !== $this->user->identity->getId()) {
            $disturber = $this->user->identity;
            if ($anon) {
                $disturber = $post->getOwner();
            }

            (new WallPostNotification($wallOwner, $post, $disturber))->emit();
        }

        $excludeMentions = [$this->user->identity->getId()];
        if ($wall > 0) {
            $excludeMentions[] = $wall;
        }

        if (!$should_be_suggested) {
            $mentions = iterator_to_array($post->resolveMentions($excludeMentions));

            foreach ($mentions as $mentionee) {
                if ($mentionee instanceof User) {
                    (new MentionNotification($mentionee, $post, $post->getOwner(), strip_tags($post->getText())))->emit();
                }
            }
        }

        if ($should_be_suggested) {
            $this->redirect("/club" . $wallOwner->getId() . "/suggested");
        } else {
            $this->redirect($wallOwner->getURL());
        }
    }

    public function renderPost(int $wall, int $post_id): void
    {
        $post = $this->posts->getPostById($wall, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->notFound();
        }

        if (!$post->canBeViewedBy($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        $this->logPostView($post, $wall);

        $this->template->post     = $post;
        if ($post->getTargetWall() > 0) {
            $this->template->wallOwner = (new Users())->get($post->getTargetWall());
            $this->template->isWallOfGroup = false;
            if ($this->template->wallOwner->isBanned()) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }
        } else {
            $this->template->wallOwner = (new Clubs())->get(abs($post->getTargetWall()));
            $this->template->isWallOfGroup = true;

            if ($this->template->wallOwner->isBanned()) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }
        }
        $this->template->cCount   = $post->getCommentsCount();
        $this->template->cPage    = (int) ($_GET["p"] ?? 1);
        $this->template->sort = $this->queryParam("sort") ?? "asc";

        $input_sort = $this->template->sort == "asc" ? "ASC" : "DESC";

        $this->template->comments = iterator_to_array($post->getComments($this->template->cPage, null, $input_sort));
    }

    public function renderLike(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $post = $this->posts->getPostById($wall, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->notFound();
        }

        if ($post->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if (!is_null($this->user)) {
            $post->toggleLike($this->user->identity);
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->returnJson([
                'success' => true,
            ]);
        }

        $this->redirect("$_SERVER[HTTP_REFERER]#postGarter=" . $post->getId());
    }

    public function renderShare(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $post = $this->posts->getPostById($wall, $post_id);

        if (!$post || $post->isDeleted()) {
            $this->notFound();
        }

        if ($post->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        $where = $this->postParam("type") ?? "wall";
        $groupId = null;
        $flags = 0;

        if ($where == "group") {
            $groupId = $this->postParam("groupId");
        }

        if (!is_null($this->user)) {
            $nPost = new Post();

            if ($where == "wall") {
                $nPost->setOwner($this->user->id);
                $nPost->setWall($this->user->id);
            } elseif ($where == "group") {
                $nPost->setOwner($this->user->id);
                $club = (new Clubs())->get((int) $groupId);

                if (!$club || !$club->canBeModifiedBy($this->user->identity)) {
                    $this->notFound();
                }

                if ($this->postParam("asGroup") == 1) {
                    $flags |= 0b10000000;
                }

                if ($this->postParam("signed") == 1) {
                    $flags |= 0b01000000;
                }

                $nPost->setWall($groupId * -1);
            }

            $nPost->setContent($this->postParam("text"));
            $nPost->setFlags($flags);
            $nPost->save();

            $nPost->attach($post);

            if ($post->getOwner(false)->getId() !== $this->user->identity->getId() && !($post->getOwner() instanceof Club)) {
                (new RepostNotification($post->getOwner(false), $post, $this->user->identity))->emit();
            }
        };

        $this->returnJson([
            "wall_owner" => $where == "wall" ? $this->user->identity->getId() : $groupId * -1,
        ]);
    }

    public function renderDelete(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $post = $this->posts->getPostById($wall, $post_id, true);
        if (!$post) {
            $this->notFound();
        }
        $user = $this->user->id;

        $wallOwner = ($wall > 0 ? (new Users())->get($wall) : (new Clubs())->get($wall * -1));

        if ($wallOwner === null) {
            $this->flashFail("err", tr("failed_to_delete_post"), tr("error_4"));
        }

        if ($wallOwner->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if ($wall < 0) {
            $canBeDeletedByOtherUser = $wallOwner->canBeModifiedBy($this->user->identity);
        } else {
            $canBeDeletedByOtherUser = false;
        }

        if (!is_null($user)) {
            if ($post->getTargetWall() < 0 && !$post->getWallOwner()->canBeModifiedBy($this->user->identity) && $post->getWallOwner()->getWallType() != 1 && $post->getSuggestionType() == 0) {
                $this->flashFail("err", tr("failed_to_delete_post"), tr("error_deleting_suggested"));
            }

            if ($post->getOwnerPost() == $user || $post->getTargetWall() == $user || $canBeDeletedByOtherUser) {
                $post->unwire();
                $post->delete();
            }
        } else {
            $this->flashFail("err", tr("failed_to_delete_post"), tr("login_required_error_comment"));
        }

        $this->redirect($wall < 0 ? "/club" . ($wall * -1) : "/id" . $wall);
    }

    public function renderPin(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $post = $this->posts->getPostById($wall, $post_id);
        if (!$post) {
            $this->notFound();
        }

        if ($post->getWallOwner()->isBanned()) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if (!$post->canBePinnedBy($this->user->identity)) {
            $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
        }

        if (($this->queryParam("act") ?? "pin") === "pin") {
            $post->pin();
        } else {
            $post->unpin();
        }

        # TODO localize message based on language and ?act=(un)pin
        $this->flashFail("succ", tr("information_-1"), tr("changes_saved_comment"));
    }

    public function renderAccept()
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("Ты дебил, это точка апи.");
        }

        $id = $this->postParam("id");
        $sign = $this->postParam("sign") == 1;
        $content = $this->postParam("new_content");

        $post = (new Posts())->get((int) $id);

        if (!$post || $post->isDeleted()) {
            $this->flashFail("err", "Error", tr("error_accepting_invalid_post"), null, true);
        }

        if ($post->getSuggestionType() == 0) {
            $this->flashFail("err", "Error", tr("error_accepting_not_suggested_post"), null, true);
        }

        if ($post->getSuggestionType() == 2) {
            $this->flashFail("err", "Error", tr("error_accepting_declined_post"), null, true);
        }

        if (!$post->canBePinnedBy($this->user->identity)) {
            $this->flashFail("err", "Error", "Can't accept this post.", null, true);
        }

        $author = $post->getOwner();

        $flags = 0;
        $flags |= 0b10000000;

        if ($sign) {
            $flags |= 0b01000000;
        }

        $post->setSuggested(0);
        $post->setCreated(time());
        $post->setApi_Source_Name(null);
        $post->setFlags($flags);

        if (mb_strlen($content) > 0) {
            $post->setContent($content);
        }

        $post->save();

        if ($author->getId() != $this->user->id) {
            (new PostAcceptedNotification($author, $post, $post->getWallOwner()))->emit();
        }

        $this->returnJson([
            "success"   => true,
            "id"        => $post->getPrettyId(),
            "new_count" => (new Posts())->getSuggestedPostsCount($post->getWallOwner()->getId()),
        ]);
    }

    public function renderDecline()
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("Ты дебил, это метод апи.");
        }

        $id = $this->postParam("id");
        $post = (new Posts())->get((int) $id);

        if (!$post || $post->isDeleted()) {
            $this->flashFail("err", "Error", tr("error_declining_invalid_post"), null, true);
        }

        if ($post->getSuggestionType() == 0) {
            $this->flashFail("err", "Error", tr("error_declining_not_suggested_post"), null, true);
        }

        if ($post->getSuggestionType() == 2) {
            $this->flashFail("err", "Error", tr("error_declining_declined_post"), null, true);
        }

        if (!$post->canBePinnedBy($this->user->identity)) {
            $this->flashFail("err", "Error", "Can't decline this post.", null, true);
        }

        $post->setSuggested(2);
        $post->setDeleted(1);
        $post->save();

        $this->returnJson([
            "success"   => true,
            "new_count" => (new Posts())->getSuggestedPostsCount($post->getWallOwner()->getId()),
        ]);
    }

    public function renderLikers(string $type, int $owner_id, int $item_id)
    {
        $this->assertUserLoggedIn();

        $item = null;
        $display_name = $type;
        switch ($type) {
            default:
                $this->notFound();
                break;
            case 'wall':
                $item = $this->posts->getPostById($owner_id, $item_id);
                $display_name = 'post';
                break;
            case 'comment':
                $item = (new \openvk\Web\Models\Repositories\Comments())->get($item_id);
                break;
            case 'photo':
                $item = (new \openvk\Web\Models\Repositories\Photos())->getByOwnerAndVID($owner_id, $item_id);
                break;
            case 'video':
                $item = (new \openvk\Web\Models\Repositories\Videos())->getByOwnerAndVID($owner_id, $item_id);
                break;
        }

        if (!$item || $item->isDeleted() || !$item->canBeViewedBy($this->user->identity)) {
            $this->notFound();
        }

        $page = (int) ($this->queryParam('p') ?? 1);
        $count = $item->getLikesCount();
        $likers = iterator_to_array($item->getLikers($page, OPENVK_DEFAULT_PER_PAGE));

        $this->template->item     = $item;
        $this->template->type     = $display_name;
        $this->template->iterator = $likers;
        $this->template->count    = $count;
        $this->template->page     = $page;
        $this->template->perPage  = OPENVK_DEFAULT_PER_PAGE;
    }
}
