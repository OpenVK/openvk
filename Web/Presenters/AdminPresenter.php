<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Database\Log;
use Chandler\Database\Logs;
use openvk\Web\Models\Entities\{Voucher, Gift, GiftCategory, User, BannedLink};
use openvk\Web\Models\Repositories\{Audios,
    ChandlerGroups,
    ChandlerUsers,
    Users,
    Clubs,
    Util\EntityStream,
    Vouchers,
    Gifts,
    BannedLinks,
    Bans,
    Photos,
    Posts,
    NoSpamLogs};
use Chandler\Database\DatabaseConnection;

final class AdminPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    private $vouchers;
    private $gifts;
    private $bannedLinks;
    private $chandlerGroups;
    private $audios;
    private $context;
    private $logs;

    public function __construct(Users $users, Clubs $clubs, Vouchers $vouchers, Gifts $gifts, BannedLinks $bannedLinks, ChandlerGroups $chandlerGroups, Audios $audios)
    {
        $this->users    = $users;
        $this->clubs    = $clubs;
        $this->vouchers = $vouchers;
        $this->gifts    = $gifts;
        $this->bannedLinks = $bannedLinks;
        $this->chandlerGroups = $chandlerGroups;
        $this->audios = $audios;

        $this->context = DatabaseConnection::i()->getContext();
        $this->logs = $this->context->table("ChandlerLogs");

        parent::__construct();
    }

    private function warnIfNoCommerce(): void
    {
        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"]) {
            $this->flash("warn", tr("admin_commerce_disabled"), tr("admin_commerce_disabled_desc"));
        }
    }

    private function warnIfLongpoolBroken(): void
    {
        bdump(is_writable(CHANDLER_ROOT . '/tmp/events.bin'));
        if (file_exists(CHANDLER_ROOT . '/tmp/events.bin') == false || is_writable(CHANDLER_ROOT . '/tmp/events.bin') == false) {
            $this->flash("warn", tr("admin_longpool_broken"), tr("admin_longpool_broken_desc", CHANDLER_ROOT . '/tmp/events.bin'));
        }
    }

    private function searchResults(object $repo, &$count)
    {
        $query = $this->queryParam("q") ?? "";
        $page  = (int) ($this->queryParam("p") ?? 1);

        $count = $repo->find($query)->size();
        return $repo->find($query)->page($page, 20);
    }

    private function searchPlaylists(&$count)
    {
        $query = $this->queryParam("q") ?? "";
        $page  = (int) ($this->queryParam("p") ?? 1);

        $count = $this->audios->findPlaylists($query)->size();
        return $this->audios->findPlaylists($query)->page($page, 20);
    }

    public function onStartup(): void
    {
        parent::onStartup();

        $this->assertPermission("admin", "access", -1);
    }

    public function renderIndex(): void
    {
        $this->warnIfLongpoolBroken();

        // Users: Registered Users
        $this->template->usersStats         = $this->users->getStatistics();
        $this->template->usersToday         = $this->context->table("profiles")->where("UNIX_TIMESTAMP(since) >=", time() - DAY)->count('*');
        $this->template->usersVerifiedCount = $this->context->table("profiles")->where("verified", true)->count('*');
        $this->template->usersDeletedCount  = $this->context->table("profiles")->where("deleted", true)->count('*');

        // Users: Instance Admins
        $admGroupUUID = $this->context->table("ChandlerACLGroupsPermissions")->where(["model" => "admin", "permission" => "access"])->fetch()->group;
        $supGroupUUID = $this->context->table("ChandlerACLGroupsPermissions")->where(["model" => "openvk\\Web\\Models\\Entities\\TicketReply", "permission" => "write"])->fetch()->group;
        $modGroupUUID = $this->context->table("ChandlerACLGroupsPermissions")->where(["model" => "openvk\\Web\\Models\\Entities\\Report", "permission" => "admin"])->fetch()->group;
        $nspGroupUUID = $this->context->table("ChandlerACLGroupsPermissions")->where(["model" => "openvk\\Web\\Models\\Entities\\Ban", "permission" => "write"])->fetch()->group;

        $groupsMissingWarnings = [];
        if (!$supGroupUUID) {
            $groupsMissingWarnings[] = "agent";
        }
        if (!$modGroupUUID) {
            $groupsMissingWarnings[] = "moder";
        }
        if (!$nspGroupUUID) {
            $groupsMissingWarnings[] = "nsp";
        }
        $this->template->groupsMissingWarnings = $groupsMissingWarnings;

        $this->template->empCnt = $this->context->table("ChandlerACLRelations")->where("group", [$admGroupUUID, $supGroupUUID, $modGroupUUID, $nspGroupUUID])->group('user')->count('*');
        $this->template->admCnt = $this->context->table("ChandlerACLRelations")->where("group", $admGroupUUID)->count('*');
        $this->template->supCnt = $this->context->table("ChandlerACLRelations")->where("group", $supGroupUUID)->count('*');
        $this->template->modCnt = $this->context->table("ChandlerACLRelations")->where("group", $modGroupUUID)->count('*');
        $this->template->nspCnt = $this->context->table("ChandlerACLRelations")->where("group", $nspGroupUUID)->count('*');

        // Users: Banned Users
        $this->template->bannedCount = $this->context->table("bans")->where("FLOOR(removed_by)", 0)->count('*');
        $this->template->bannedForeverCount = $this->context->table("bans")->where(["FLOOR(removed_by)" => 0, "exp" => 0])->count('*');
        $this->template->canBeUnbannedNowCount = $this->context->table("bans")->where(["FLOOR(removed_by)" => 0, "exp <=" => time(), "exp >" => 0])->count('*');

        // Support and Moderation: Tickets
        $ticketsCount           = $this->context->table("tickets")->count('*');
        $ticketsCountToday      = $this->context->table("tickets")->where("created >= ?", time() - DAY)->count('*');
        $ticketsProcessingCount = $this->context->table("tickets")->where("type", 0)->count('*');
        $ticketsWithAnswerCount = $this->context->table("tickets")->where("type", 1)->count('*');
        $ticketsClosedCount     = $this->context->table("tickets")->where("type", 2)->count('*');

        $this->template->ticketsCount           = $ticketsCount;
        $this->template->ticketsCountToday      = $ticketsCountToday;
        $this->template->ticketsProcessingCount = $ticketsProcessingCount;
        $this->template->ticketsWithAnswerCount = $ticketsWithAnswerCount;
        $this->template->ticketsClosedCount     = $ticketsClosedCount;

        // Support and Moderation: Reports
        $this->template->reportsCount      = $this->context->table("reports")->count('*');
        $this->template->reportsCountToday = $this->context->table("reports")->where("created >=", time() - DAY)->count('*');

        // Support and Moderation: noSpam
        $nspTemplatesCount = 0;
        $nspContentCount   = 0;
        foreach ((new NoSpamLogs())->getList() as $nsplog) {
            $nspTemplatesCount++;
            $nspContentCount += $nsplog->getCount();
        }
        $this->template->nspTemplatesCount = $nspTemplatesCount;
        $this->template->nspContentCount   = $nspContentCount;

        // Content: Groups
        $this->template->groupsCount         = $this->context->table("groups")->count('*');
        $this->template->groupsVerifiedCount = $this->context->table("groups")->where("verified", true)->count('*');
        $this->template->groupsBannedCount   = $this->context->table("groups")->where("block_reason !=", "")->count('*');

        // Content: Other
        $this->template->postsCount     = (new Posts())->getCount();
        $this->template->messagesCount  = $this->context->table("messages")->count('*');
        $this->template->photosCount    = $this->context->table("photos")->count('*');
        $this->template->videosCount    = $this->context->table("videos")->count('*');
        $this->template->audiosCount    = $this->context->table("audios")->count('*');
        $this->template->notesCount     = $this->context->table("notes")->count('*');
        $this->template->appsCount      = $this->context->table("apps")->count('*');
        $this->template->documentsCount = $this->context->table("documents")->count('*');
    }

    public function renderUsers(): void
    {
        $this->template->users = $this->searchResults($this->users, $this->template->count);
    }

    public function renderUser(int $id): void
    {
        $user = $this->users->get($id);
        if (!$user) {
            $this->notFound();
        }

        $this->template->user = $user;
        $this->template->c_groups_list = (new ChandlerGroups())->getList();
        $this->template->c_memberships = $this->chandlerGroups->getUsersMemberships($user->getChandlerGUID());

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        switch ($_POST["act"] ?? "info") {
            default:
            case "info":
                $user->setFirst_Name($this->postParam("first_name"));
                $user->setLast_Name($this->postParam("last_name"));
                $user->setPseudo($this->postParam("nickname"));
                $user->setStatus($this->postParam("status"));
                $user->setHide_Global_Feed(empty($this->postParam("hide_global_feed") ? 0 : 1));
                if (!$user->setShortCode(empty($this->postParam("shortcode")) ? null : $this->postParam("shortcode"))) {
                    $this->flash("err", tr("error"), tr("error_shorturl_incorrect"));
                }
                $user->changeEmail($this->postParam("email"));
                if ($user->onlineStatus() != $this->postParam("online")) {
                    $user->setOnline(intval($this->postParam("online")));
                }
                $user->setVerified(empty($this->postParam("verify") ? 0 : 1));
                if ($this->postParam("add-to-group")) {
                    if (!(new ChandlerGroups())->isUserAMember($this->postParam("add-to-group"), $user->getChandlerGUID())) {
                        $query = "INSERT INTO `ChandlerACLRelations` (`user`, `group`) VALUES ('" . $user->getChandlerGUID() . "', '" . $this->postParam("add-to-group") . "')";
                        DatabaseConnection::i()->getConnection()->query($query);
                    } else {
                        $this->flash("err", tr("error"), tr("c_user_is_already_in_group"));
                    }
                }
                if ($this->postParam("password")) {
                    $user->getChandlerUser()->updatePassword($this->postParam("password"));
                }

                $user->save();

                break;
        }
    }

    public function renderClubs(): void
    {
        $this->template->clubs = $this->searchResults($this->clubs, $this->template->count);
    }

    public function renderClub(int $id): void
    {
        $club = $this->clubs->get($id);
        if (!$club) {
            $this->notFound();
        }

        $this->template->mode = in_array($this->queryParam("act"), ["main", "ban", "followers"]) ? $this->queryParam("act") : "main";

        $this->template->club = $club;

        $this->template->followers = $this->template->club->getFollowers((int) ($this->queryParam("p") ?? 1));

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        switch ($this->queryParam("act")) {
            default:
            case "main":
                $club->setOwner($this->postParam("id_owner"));
                $club->setName($this->postParam("name"));
                $club->setAbout($this->postParam("about"));
                $club->setShortCode($this->postParam("shortcode"));
                $club->setVerified(empty($this->postParam("verify") ? 0 : 1));
                $club->setHide_From_Global_Feed(empty($this->postParam("hide_from_global_feed") ? 0 : 1));
                $club->setEnforce_Hiding_From_Global_Feed(empty($this->postParam("enforce_hiding_from_global_feed") ? 0 : 1));
                $club->setEnforce_Main_Note_Expanded(empty($this->postParam("enforce_main_note_expanded") ? 0 : 1));
                $club->setEnforce_Wiki_Pages_Disabled(empty($this->postParam("enforce_wiki_pages_disabled") ? 0 : 1));
                $club->save();
                break;
            case "ban":
                $reason = mb_strlen(trim($this->postParam("ban_reason"))) > 0 ? $this->postParam("ban_reason") : null;
                $club->setBlock_reason($reason);
                $club->save();
                break;
        }
    }

    public function renderVouchers(): void
    {
        $this->warnIfNoCommerce();

        $this->template->count    = $this->vouchers->size();
        $this->template->vouchers = iterator_to_array($this->vouchers->enumerate((int) ($this->queryParam("p") ?? 1)));
    }

    public function renderVoucher(int $id): void
    {
        $this->warnIfNoCommerce();

        $voucher = null;
        $this->template->form = (object) [];
        if ($id === 0) {
            $this->template->form->id     = 0;
            $this->template->form->token  = null;
            $this->template->form->coins  = 0;
            $this->template->form->rating = 0;
            $this->template->form->usages = -1;
            $this->template->form->users  = [];
        } else {
            $voucher = $this->vouchers->get($id);
            if (!$voucher) {
                $this->notFound();
            }

            $this->template->form->id     = $voucher->getId();
            $this->template->form->token  = $voucher->getToken();
            $this->template->form->coins  = $voucher->getCoins();
            $this->template->form->rating = $voucher->getRating();
            $this->template->form->usages = $voucher->getRemainingUsages();
            $this->template->form->users  = iterator_to_array($voucher->getUsers());

            if ($this->template->form->usages === INF) {
                $this->template->form->usages = -1;
            } else {
                $this->template->form->usages = (int) $this->template->form->usages;
            }
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $voucher ??= new Voucher();
        $voucher->setCoins((int) $this->postParam("coins"));
        $voucher->setRating((int) $this->postParam("rating"));
        $voucher->setRemainingUsages($this->postParam("usages") === '-1' ? INF : ((int) $this->postParam("usages")));
        if (!empty($tok = $this->postParam("token")) && strlen($tok) === 24) {
            $voucher->setToken($tok);
        }

        $voucher->save();

        $this->redirect("/admin/vouchers/id" . $voucher->getId());
    }

    public function renderGiftCategories(): void
    {
        $this->warnIfNoCommerce();

        $this->template->act        = $this->queryParam("act") ?? "list";
        $this->template->categories = iterator_to_array($this->gifts->getCategories((int) ($this->queryParam("p") ?? 1), null, $this->template->count));
    }

    public function renderGiftCategory(string $slug, int $id): void
    {
        $this->warnIfNoCommerce();

        $cat = null;
        $gen = false;
        if ($id !== 0) {
            $cat = $this->gifts->getCat($id);
            if (!$cat) {
                $this->notFound();
            } elseif ($cat->getSlug() !== $slug) {
                $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $id . ".meta");
            }
        } else {
            $gen = true;
            $cat = new GiftCategory();
        }

        $this->template->form = (object) [];
        $this->template->form->id        = $id;
        $this->template->form->languages = [];
        foreach (getLanguages() as $language) {
            $language = (object) $language;
            $this->template->form->languages[$language->code] = (object) [];

            $this->template->form->languages[$language->code]->name        = $gen ? "" : ($cat->getName($language->code, true) ?? "");
            $this->template->form->languages[$language->code]->description = $gen ? "" : ($cat->getDescription($language->code, true) ?? "");
        }

        $this->template->form->languages["master"] = (object) [
            "name"        => $gen ? "Unknown Name" : $cat->getName(),
            "description" => $gen ? "" : $cat->getDescription(),
        ];

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        if ($gen) {
            $cat->setAutoQuery(null);
            $cat->save();
        }

        $cat->setName("_", $this->postParam("name_master"));
        $cat->setDescription("_", $this->postParam("description_master"));
        foreach (getLanguages() as $language) {
            $code = $language["code"];
            if (!empty($this->postParam("name_$code") ?? null)) {
                $cat->setName($code, $this->postParam("name_$code"));
            }

            if (!empty($this->postParam("description_$code") ?? null)) {
                $cat->setDescription($code, $this->postParam("description_$code"));
            }
        }

        $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $cat->getId() . ".meta");
    }

    public function renderGifts(string $catSlug, int $catId): void
    {
        $this->warnIfNoCommerce();

        $cat = $this->gifts->getCat($catId);
        if (!$cat) {
            $this->notFound();
        } elseif ($cat->getSlug() !== $catSlug) {
            $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $catId . "/");
        }

        $this->template->cat   = $cat;
        $this->template->gifts = iterator_to_array($cat->getGifts((int) ($this->queryParam("p") ?? 1), null, $this->template->count));
    }

    public function renderGift(int $id): void
    {
        $this->warnIfNoCommerce();

        $gift = $this->gifts->get($id);
        $act  = $this->queryParam("act") ?? "edit";
        switch ($act) {
            case "delete":
                $this->assertNoCSRF();
                if (!$gift) {
                    $this->notFound();
                }

                $gift->delete();
                $this->flashFail("succ", tr("admin_gift_moved_successfully"), tr("admin_gift_moved_to_recycle"));
                break;
            case "copy":
            case "move":
                $this->assertNoCSRF();
                if (!$gift) {
                    $this->notFound();
                }

                $catFrom = $this->gifts->getCat((int) ($this->queryParam("from") ?? 0));
                $catTo   = $this->gifts->getCat((int) ($this->queryParam("to") ?? 0));
                if (!$catFrom || !$catTo || !$catFrom->hasGift($gift)) {
                    $this->badRequest();
                }

                if ($act === "move") {
                    $catFrom->removeGift($gift);
                }

                $catTo->addGift($gift);

                $name = $catTo->getName();
                $this->flash("succ", tr("admin_gift_moved_successfully"), "This gift will now be in <b>$name</b>.");
                $this->redirect("/admin/gifts/" . $catTo->getSlug() . "." . $catTo->getId() . "/");
                break;
            default:
            case "edit":
                $gen = false;
                if (!$gift) {
                    $gen  = true;
                    $gift = new Gift();
                }

                $this->template->form = (object) [];
                $this->template->form->id     = $id;
                $this->template->form->name   = $gen ? "New Gift (1)" : $gift->getName();
                $this->template->form->price  = $gen ? 0 : $gift->getPrice();
                $this->template->form->usages = $gen ? 0 : $gift->getUsages();
                $this->template->form->limit  = $gen ? -1 : ($gift->getLimit() === INF ? -1 : $gift->getLimit());
                $this->template->form->pic    = $gen ? null : $gift->getImage(Gift::IMAGE_URL);

                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    return;
                }

                $limit = $this->postParam("limit") ?? $this->template->form->limit;
                $limit = $limit == "-1" ? INF : (float) $limit;
                $gift->setLimit($limit, is_null($this->postParam("reset_limit")) ? Gift::PERIOD_SET_IF_NONE : Gift::PERIOD_SET);

                $gift->setName($this->postParam("name"));
                $gift->setPrice((int) $this->postParam("price"));
                $gift->setUsages((int) $this->postParam("usages"));
                if (isset($_FILES["pic"]) && $_FILES["pic"]["error"] === UPLOAD_ERR_OK) {
                    if (!$gift->setImage($_FILES["pic"]["tmp_name"])) {
                        $this->flashFail("err", tr("error_when_saving_gift"), tr("error_when_saving_gift_bad_image"));
                    }
                } elseif ($gen) {
                    # If there's no gift pic but it's newly created
                    $this->flashFail("err", tr("error_when_saving_gift"), tr("error_when_saving_gift_no_image"));
                }

                $gift->save();

                if ($gen && !is_null($cat = $this->postParam("_cat"))) {
                    $cat = $this->gifts->getCat((int) $cat);
                    if (!is_null($cat)) {
                        $cat->addGift($gift);
                    }
                }

                $this->redirect("/admin/gifts/id" . $gift->getId());
        }
    }

    public function renderFiles(): void {}

    public function renderQuickBan(int $id): void
    {
        $this->assertNoCSRF();

        if (str_contains($this->queryParam("reason"), "*")) {
            exit(json_encode([ "error" => "Incorrect reason" ]));
        }

        $unban_time = strtotime($this->queryParam("date")) ?: "permanent";

        $user = $this->users->get($id);
        if (!$user) {
            exit(json_encode([ "error" => "User does not exist" ]));
        }

        if ($this->queryParam("incr")) {
            $unban_time = time() + $user->getNewBanTime();
        }

        $user->ban($this->queryParam("reason"), true, $unban_time, $this->user->identity->getId());
        exit(json_encode([ "success" => true, "reason" => $this->queryParam("reason") ]));
    }

    public function renderQuickUnban(int $id): void
    {
        $this->assertNoCSRF();

        $user = $this->users->get($id);
        if (!$user) {
            exit(json_encode([ "error" => "User does not exist" ]));
        }

        $ban = (new Bans())->get((int) $user->getRawBanReason());
        if (!$ban || $ban->isOver()) {
            exit(json_encode([ "error" => "User is not banned" ]));
        }

        $ban->setRemoved_Manually(true);
        $ban->setRemoved_By($this->user->identity->getId());
        $ban->save();

        $user->setBlock_Reason(null);
        // $user->setUnblock_time(NULL);
        $user->save();
        exit(json_encode([ "success" => true ]));
    }

    public function renderQuickWarn(int $id): void
    {
        $this->assertNoCSRF();

        $user = $this->users->get($id);
        if (!$user) {
            exit(json_encode([ "error" => "User does not exist" ]));
        }

        $user->adminNotify("⚠️ " . $this->queryParam("message"));
        exit(json_encode([ "message" => $this->queryParam("message") ]));
    }

    public function renderBannedLinks(): void
    {
        $this->template->links = $this->bannedLinks->getList((int) $this->queryParam("p") ?: 1);
        $this->template->users = new Users();
    }

    public function renderBannedLink(int $id): void
    {
        $this->template->form = (object) [];

        if ($id === 0) {
            $this->template->form->id     = 0;
            $this->template->form->link   = null;
            $this->template->form->reason = null;
        } else {
            $link = (new BannedLinks())->get($id);
            if (!$link) {
                $this->notFound();
            }

            $this->template->form->id     = $link->getId();
            $this->template->form->link   = $link->getDomain();
            $this->template->form->reason = $link->getReason();
            $this->template->form->regexp = $link->getRawRegexp();
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $link = (new BannedLinks())->get($id);

        $new_domain = parse_url($this->postParam("link"))["host"];
        $new_reason = $this->postParam("reason") ?: null;

        $lid = $id;

        if ($link) {
            $link->setDomain($new_domain ?? $this->postParam("link"));
            $link->setReason($new_reason);
            $link->setRegexp_rule(mb_strlen(trim($this->postParam("regexp"))) > 0 ? $this->postParam("regexp") : "");
            $link->save();
        } else {
            if (!$new_domain) {
                $this->flashFail("err", tr("error"), tr("admin_banned_link_not_specified"));
            }

            $link = new BannedLink();
            $link->setDomain($new_domain);
            $link->setReason($new_reason);
            $link->setRegexp_rule(mb_strlen(trim($this->postParam("regexp"))) > 0 ? $this->postParam("regexp") : "");
            $link->setInitiator($this->user->identity->getId());
            $link->save();

            $lid = $link->getId();
        }

        $this->redirect("/admin/bannedLink/id" . $lid);
    }

    public function renderUnbanLink(int $id): void
    {
        $link = (new BannedLinks())->get($id);

        if (!$link) {
            $this->flashFail("err", tr("error"), tr("admin_banned_link_not_found"));
        }

        $link->delete(false);

        $this->redirect("/admin/bannedLinks");
    }

    public function renderBansHistory(int $user_id): void
    {
        $user = (new Users())->get($user_id);
        if (!$user) {
            $this->notFound();
        }

        $this->template->bans = (new Bans())->getByUser($user_id);
    }

    public function renderChandlerGroups(): void
    {
        $this->template->groups = (new ChandlerGroups())->getList();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        if ($this->postParam("fixChandlerGroups")) {
            $guid = $this->user->identity->getChandlerGUID();

            $req = "";
            if ($this->postParam("agent")) {
                $req = $req . <<<'SQL'
                        INSERT INTO `ChandlerGroups` VALUES (NULL, "OVK\\SupportAgents", NULL);
                        INSERT INTO `ChandlerACLGroupsPermissions` VALUES ((SELECT id FROM ChandlerGroups WHERE name = "OVK\\SupportAgents"), "openvk\\Web\\Models\\Entities\\TicketReply", 0, "write", 1);
                        INSERT INTO `ChandlerACLRelations` VALUES ("{GUID}", (SELECT id FROM ChandlerGroups WHERE name = "OVK\\SupportAgents"), 64);
                    SQL;
            }

            if ($this->postParam("moder")) {
                $req = $req . <<<'SQL'
                        INSERT INTO `ChandlerGroups` VALUES (NULL, "OVK\\Moderators", NULL);
                        INSERT INTO `ChandlerACLGroupsPermissions` VALUES ((SELECT id FROM ChandlerGroups WHERE name = "OVK\\Moderators"), "openvk\\Web\\Models\\Entities\\Report", 0, "admin", 1);
                        INSERT INTO `ChandlerACLRelations` VALUES ("{GUID}", (SELECT id FROM ChandlerGroups WHERE name = "OVK\\Moderators"), 64);
                    SQL;
            }

            if ($this->postParam("nsp")) {
                $req = $req . <<<'SQL'
                        INSERT INTO `ChandlerGroups` VALUES (NULL, "OVK\\SpamAnalysts", NULL);
                        INSERT INTO `ChandlerACLGroupsPermissions` VALUES ((SELECT id FROM ChandlerGroups WHERE name = "OVK\\SpamAnalysts"), "openvk\\Web\\Models\\Entities\\Ban", 0, "write", 1);
                        INSERT INTO `ChandlerACLRelations` VALUES ("{GUID}", (SELECT id FROM ChandlerGroups WHERE name = "OVK\\SpamAnalysts"), 64);
                    SQL;
            }

            if (mb_strlen($req) > 0) {
                $req = str_replace('{GUID}', $guid, $req);
                DatabaseConnection::i()->getConnection()->query($req);
                $this->flashFail("succ", tr("changes_saved"));
            }

            return;
        }

        $req = "INSERT INTO `ChandlerGroups` (`name`) VALUES ('" . $this->postParam("name") . "')";
        DatabaseConnection::i()->getConnection()->query($req);
    }

    public function renderChandlerGroup(string $UUID): void
    {
        $DB = DatabaseConnection::i()->getConnection();

        if (is_null($DB->query("SELECT * FROM `ChandlerGroups` WHERE `id` = '$UUID'")->fetch())) {
            $this->flashFail("err", tr("error"), tr("c_group_not_found"));
        }

        $this->template->group = (new ChandlerGroups())->get($UUID);
        $this->template->mode = in_array(
            $this->queryParam("act"),
            [
                "main",
                "members",
                "permissions",
                "removeMember",
                "removePermission",
                "delete",
            ]
        ) ? $this->queryParam("act") : "main";
        $this->template->members = (new ChandlerGroups())->getMembersById($UUID);
        $this->template->perms = (new ChandlerGroups())->getPermissionsById($UUID);

        if ($this->template->mode == "removeMember") {
            $where = "`user` = '" . $this->queryParam("uid") . "' AND `group` = '$UUID'";

            if (is_null($DB->query("SELECT * FROM `ChandlerACLRelations` WHERE " . $where)->fetch())) {
                $this->flashFail("err", tr("error"), tr("c_user_is_not_in_group"));
            }

            $DB->query("DELETE FROM `ChandlerACLRelations` WHERE " . $where);
            $this->flashFail("succ", tr("changes_saved"), tr("c_user_removed_from_group"));
        } elseif ($this->template->mode == "removePermission") {
            $where = "`model` = '" . trim(addslashes($this->queryParam("model"))) . "' AND `permission` = '" . $this->queryParam("perm") . "' AND `group` = '$UUID'";

            if (is_null($DB->query("SELECT * FROM `ChandlerACLGroupsPermissions` WHERE $where"))) {
                $this->flashFail("err", tr("error"), tr("c_permission_not_found"));
            }

            $DB->query("DELETE FROM `ChandlerACLGroupsPermissions` WHERE $where");
            $this->flashFail("succ", tr("changes_saved"), tr("c_permission_removed_from_group"));
        } elseif ($this->template->mode == "delete") {
            $DB->query("DELETE FROM `ChandlerGroups` WHERE `id` = '$UUID'");
            $DB->query("DELETE FROM `ChandlerACLGroupsPermissions` WHERE `group` = '$UUID'");
            $DB->query("DELETE FROM `ChandlerACLRelations` WHERE `group` = '$UUID'");

            $this->flashFail("succ", tr("changes_saved"), tr("c_group_removed"));
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $req = "";

        if ($this->template->mode == "main") {
            if ($this->postParam("delete")) {
                $req = "DELETE FROM `ChandlerGroups` WHERE `id`='$UUID'";
            } else {
                $req = "UPDATE `ChandlerGroups` SET `name`='" . $this->postParam('name') . "' , `color`='" . $this->postParam("color") . "' WHERE `id`='$UUID'";
            }
        }

        if ($this->template->mode == "members") {
            if ($this->postParam("uid")) {
                if (is_null((new ChandlerUsers())->getById($this->postParam("uid")))) {
                    $this->flashFail("err", tr("error"), tr("profile_not_found"));
                }
                if ((new ChandlerGroups())->isUserAMember($UUID, $this->postParam("uid"))) {
                    $this->flashFail("err", tr("error"), tr("c_user_is_already_in_group"));
                }
            }
        }

        $req = "INSERT INTO `ChandlerACLRelations` (`user`, `group`, `priority`) VALUES ('" . $this->postParam("uid") . "', '$UUID', 32)";

        if ($this->template->mode == "permissions") {
            $req = "INSERT INTO `ChandlerACLGroupsPermissions` (`group`, `model`, `permission`, `context`) VALUES ('$UUID', '" . trim(addslashes($this->postParam("model"))) . "', '" . $this->postParam("permission") . "', 0)";
        }

        $DB->query($req);
        $this->flashFail("succ", tr("changes_saved"));
    }

    public function renderChandlerUser(string $UUID): void
    {
        if (!$UUID) {
            $this->notFound();
        }

        $c_user = (new ChandlerUsers())->getById($UUID);
        $user = $this->users->getByChandlerUser($c_user);
        if (!$user) {
            $this->notFound();
        }

        $this->redirect("/admin/users/id" . $user->getId());
    }

    public function renderMusic(): void
    {
        $this->template->mode = in_array($this->queryParam("act"), ["audios", "playlists"]) ? $this->queryParam("act") : "audios";
        if ($this->template->mode === "audios") {
            $this->template->audios = $this->searchResults($this->audios, $this->template->count);
        } else {
            $this->template->playlists = $this->searchPlaylists($this->template->count);
        }
    }

    public function renderEditMusic(int $audio_id): void
    {
        $audio = $this->audios->get($audio_id);
        $this->template->audio = $audio;

        try {
            $this->template->owner = $audio->getOwner()->getId();
        } catch (\Throwable $e) {
            $this->template->owner = 1;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $audio->setName($this->postParam("name"));
            $audio->setPerformer($this->postParam("performer"));
            $audio->setLyrics($this->postParam("text"));
            $audio->setGenre($this->postParam("genre"));
            $audio->setOwner((int) $this->postParam("owner"));
            $audio->setExplicit(!empty($this->postParam("explicit")));
            $audio->setDeleted(!empty($this->postParam("deleted")));
            $audio->setWithdrawn(!empty($this->postParam("withdrawn")));
            $audio->save();
        }
    }

    public function renderEditPlaylist(int $playlist_id): void
    {
        $playlist = $this->audios->getPlaylist($playlist_id);
        $this->template->playlist = $playlist;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $playlist->setName($this->postParam("name"));
            $playlist->setDescription($this->postParam("description"));
            $playlist->setCover_Photo_Id((int) $this->postParam("photo"));
            $playlist->setOwner((int) $this->postParam("owner"));
            $playlist->setDeleted(!empty($this->postParam("deleted")));
            $playlist->save();
        }
    }

    public function renderLogs(): void
    {
        $filter = [];

        if ($this->queryParam("id")) {
            $id = (int) $this->queryParam("id");
            $filter["id"] = $id;
            $this->template->id = $id;
        }
        if ($this->queryParam("type") !== null && $this->queryParam("type") !== "any") {
            $type = in_array($this->queryParam("type"), [0, 1, 2, 3]) ? (int) $this->queryParam("type") : 0;
            $filter["type"] = $type;
            $this->template->type = $type;
        }
        if ($this->queryParam("uid")) {
            $user = $this->queryParam("uid");
            $filter["user"] = $user;
            $this->template->user = $user;
        }
        if ($this->queryParam("obj_id")) {
            $obj_id = (int) $this->queryParam("obj_id");
            $filter["object_id"] = $obj_id;
            $this->template->obj_id = $obj_id;
        }
        if ($this->queryParam("obj_type") !== null && $this->queryParam("obj_type") !== "any") {
            $obj_type = "openvk\\Web\\Models\\Entities\\" . $this->queryParam("obj_type");
            $filter["object_model"] = $obj_type;
            $this->template->obj_type = $obj_type;
        }

        $logs = iterator_to_array((new Logs())->search($filter));
        $this->template->logs = $logs;
        $this->template->object_types = (new Logs())->getTypes();
    }
}
