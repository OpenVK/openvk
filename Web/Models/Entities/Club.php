<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Manager};
use openvk\Web\Models\Repositories\{Users, Clubs, Albums, Managers, Notes, Posts};
use Nette\Database\Table\{ActiveRow, GroupedSelection};
use Chandler\Database\DatabaseConnection as DB;
use Chandler\Security\User as ChandlerUser;

class Club extends RowModel
{
    use Traits\TBackDrops;
    use Traits\TSubscribable;
    use Traits\TAudioStatuses;
    use Traits\TIgnorable;
    protected $tableName = "groups";

    public const TYPE_GROUP  = 1;
    public const TYPE_PUBLIC = 1;
    public const TYPE_EVENT  = 2;

    public const OPEN    = 0;
    public const CLOSED  = 1;
    public const PRIVATE = 2;

    public const NOT_RELATED  = 0;
    public const SUBSCRIBED   = 1;
    public const REQUEST_SENT = 2;

    public const WALL_CLOSED   = 0;
    public const WALL_OPEN     = 1;
    public const WALL_LIMITED  = 2;

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getAvatarPhoto(): ?Photo
    {
        $avAlbum  = (new Albums())->getClubAvatarAlbum($this);
        $avCount  = $avAlbum->getPhotosCount();
        $avPhotos = $avAlbum->getPhotos($avCount, 1);

        return iterator_to_array($avPhotos)[0] ?? null;
    }

    public function getAvatarUrl(string $size = "miniscule", $avPhoto = null): string
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        if (!$avPhoto) {
            $avPhoto = $this->getAvatarPhoto();
        }

        return is_null($avPhoto) ? "$serverUrl/assets/packages/static/openvk/img/camera_200.png" : $avPhoto->getURLBySizeId($size);
    }

    public function getWallType(): int
    {
        return $this->getRecord()->wall;
    }

    public function getAvatarLink(): string
    {
        $avPhoto = $this->getAvatarPhoto();
        if (!$avPhoto) {
            return "javascript:void(0)";
        }

        $pid = $avPhoto->getPrettyId();
        $aid = (new Albums())->getClubAvatarAlbum($this)->getId();

        return "/photo$pid?from=album$aid";
    }

    public function getURL(): string
    {
        if (!is_null($this->getShortCode())) {
            return "/" . $this->getShortCode();
        } else {
            return "/club" . $this->getId();
        }
    }

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getCanonicalName(): string
    {
        return $this->getName();
    }

    public function getOwner(): ?User
    {
        return (new Users())->get($this->getRecord()->owner);
    }

    public function getOwnerComment(): string
    {
        return is_null($this->getRecord()->owner_comment) ? "" : $this->getRecord()->owner_comment;
    }

    public function isOwnerHidden(): bool
    {
        return (bool) $this->getRecord()->owner_hidden;
    }

    public function isOwnerClubPinned(): bool
    {
        return (bool) $this->getRecord()->owner_club_pinned;
    }

    public function getDescription(): ?string
    {
        return $this->getRecord()->about;
    }

    public function getDescriptionHtml(): ?string
    {
        if (!is_null($this->getDescription())) {
            return nl2br(htmlspecialchars($this->getDescription(), ENT_DISALLOWED | ENT_XHTML));
        } else {
            return null;
        }
    }

    public function getShortCode(): ?string
    {
        return $this->getRecord()->shortcode;
    }

    public function getBanReason(): ?string
    {
        return $this->getRecord()->block_reason;
    }

    public function getOpennesStatus(): int
    {
        return $this->getRecord()->closed;
    }

    public function getAdministratorsListDisplay(): int
    {
        return $this->getRecord()->administrators_list_display;
    }

    public function isEveryoneCanCreateTopics(): bool
    {
        return (bool) $this->getRecord()->everyone_can_create_topics;
    }

    public function isDisplayTopicsAboveWallEnabled(): bool
    {
        return (bool) $this->getRecord()->display_topics_above_wall;
    }

    public function isHideFromGlobalFeedEnabled(): bool
    {
        return (bool) $this->getRecord()->hide_from_global_feed;
    }

    public function isHidingFromGlobalFeedEnforced(): bool
    {
        return (bool) $this->getRecord()->enforce_hiding_from_global_feed;
    }

    public function getType(): int
    {
        return $this->getRecord()->type;
    }

    public function isVerified(): bool
    {
        return (bool) $this->getRecord()->verified;
    }

    public function isBanned(): bool
    {
        return !is_null($this->getBanReason());
    }

    public function canPost(): bool
    {
        return (bool) $this->getRecord()->wall;
    }


    public function setShortCode(?string $code = null): ?bool
    {
        if (!is_null($code)) {
            if (!preg_match("%^[a-z][a-z0-9\\.\\_]{0,30}[a-z0-9]$%", $code)) {
                return false;
            }
            if (in_array($code, OPENVK_ROOT_CONF["openvk"]["preferences"]["shortcodes"]["forbiddenNames"])) {
                return false;
            }
            if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$code")[0]->presenter !== "UnknownTextRouteStrategy") {
                return false;
            }

            $pUser = DB::i()->getContext()->table("profiles")->where("shortcode", $code)->fetch();
            if (!is_null($pUser)) {
                return false;
            }
        }

        $this->stateChanges("shortcode", $code);
        return true;
    }

    public function setWall(int $type)
    {
        if ($type > 2 || $type < 0) {
            throw new \LogicException("Invalid wall");
        }

        $this->stateChanges("wall", $type);
    }

    public function isSubscriptionAccepted(User $user): bool
    {
        return !is_null($this->getRecord()->related("subscriptions.follower")->where([
            "follower" => $this->getId(),
            "target"   => $user->getId(),
        ])->fetch());
        ;
    }

    public function getPostViewStats(bool $unique = false): ?array
    {
        $edb = eventdb();
        if (!$edb) {
            return null;
        }

        $subs  = [];
        $viral = [];
        $total = [];
        for ($i = 1; $i < 8; $i++) {
            $begin = strtotime("-" . $i . "day midnight");
            $end   = $i === 1 ? time() + 10 : strtotime("-" . ($i - 1) . "day midnight");

            $query  = "SELECT COUNT(" . ($unique ? "DISTINCT profile" : "*") . ") AS cnt FROM postViews";
            $query .= " WHERE `group`=1 AND owner=" . $this->getId();
            $query .= " AND timestamp > $begin AND timestamp < $end";

            $sub = $edb->getConnection()->query("$query AND NOT subscribed=0")->fetch()->cnt;
            $vir = $edb->getConnection()->query("$query AND subscribed=0")->fetch()->cnt;
            $subs[]  = $sub;
            $viral[] = $vir;
            $total[] = $sub + $vir;
        }

        return [
            "total" => [
                "x" => array_reverse(range(1, 7)),
                "y" => $total,
                "type" => "scatter",
                "line" => [
                    "shape" => "spline",
                    "color" => "#597da3",
                ],
                "name" => $unique ? tr("full_coverage") : tr("all_views"),
            ],
            "subs"  => [
                "x" => array_reverse(range(1, 7)),
                "y" => $subs,
                "type" => "scatter",
                "line" => [
                    "shape" => "spline",
                    "color" => "#b05c91",
                ],
                "fill" => "tozeroy",
                "name" => $unique ? tr("subs_coverage") : tr("subs_views"),
            ],
            "viral" => [
                "x" => array_reverse(range(1, 7)),
                "y" => $viral,
                "type" => "scatter",
                "line" => [
                    "shape" => "spline",
                    "color" => "#4d9fab",
                ],
                "fill" => "tozeroy",
                "name" => $unique ? tr("viral_coverage") : tr("viral_views"),
            ],
        ];
    }

    public function getSubscriptionStatus(User $user): bool
    {
        $subbed = !is_null($this->getRecord()->related("subscriptions.target")->where([
            "target"   => $this->getId(),
            "model"    => static::class,
            "follower" => $user->getId(),
        ])->fetch());

        return $subbed && ($this->getOpennesStatus() === static::CLOSED ? $this->isSubscriptionAccepted($user) : true);
    }

    public function getFollowersQuery(string $sort = "follower ASC"): GroupedSelection
    {
        $query = $this->getRecord()->related("subscriptions.target");

        if ($this->getOpennesStatus() === static::OPEN) {
            $query = $query->where("model", "openvk\\Web\\Models\\Entities\\Club")->order($sort);
        } else {
            return false;
        }

        return $query->group("follower");
    }

    public function getFollowersCount(): int
    {
        return sizeof($this->getFollowersQuery());
    }

    public function getFollowers(int $page = 1, int $perPage = 6, string $sort = "target DESC"): \Traversable
    {
        $rels = $this->getFollowersQuery($sort)->page($page, $perPage);

        foreach ($rels as $rel) {
            $rel = (new Users())->get($rel->follower);
            if (!$rel) {
                continue;
            }

            yield $rel;
        }
    }

    public function getSuggestedPostsCount(User $user = null)
    {
        $count = 0;

        if (is_null($user)) {
            return null;
        }

        if ($this->canBeModifiedBy($user)) {
            $count = (new Posts())->getSuggestedPostsCount($this->getId());
        } else {
            $count = (new Posts())->getSuggestedPostsCountByUser($this->getId(), $user->getId());
        }

        return $count;
    }

    public function getManagers(int $page = 1, bool $ignoreHidden = false): \Traversable
    {
        $rels = $this->getRecord()->related("group_coadmins.club")->page($page, 6);
        if ($ignoreHidden) {
            $rels = $rels->where("hidden", false);
        }

        foreach ($rels as $rel) {
            $rel = (new Managers())->get($rel->id);
            if (!$rel) {
                continue;
            }

            yield $rel;
        }
    }

    public function getManager(User $user, bool $ignoreHidden = false): ?Manager
    {
        $manager = (new Managers())->getByUserAndClub($user->getId(), $this->getId());

        if ($ignoreHidden && $manager !== null && $manager->isHidden()) {
            return null;
        }

        return $manager;
    }

    public function getManagersCount(bool $ignoreHidden = false): int
    {
        if ($ignoreHidden) {
            return sizeof($this->getRecord()->related("group_coadmins.club")->where("hidden", false)) + (int) !$this->isOwnerHidden();
        }

        return sizeof($this->getRecord()->related("group_coadmins.club")) + 1;
    }

    public function addManager(User $user, ?string $comment = null): void
    {
        DB::i()->getContext()->table("group_coadmins")->insert([
            "club" => $this->getId(),
            "user" => $user->getId(),
            "comment" => $comment,
        ]);
    }

    public function removeManager(User $user): void
    {
        DB::i()->getContext()->table("group_coadmins")->where([
            "club" => $this->getId(),
            "user" => $user->getId(),
        ])->delete();
    }

    public function canBeModifiedBy(User $user): bool
    {
        $id = $user->getId();
        if ($this->getOwner()->getId() === $id) {
            return true;
        }

        return !is_null($this->getRecord()->related("group_coadmins.club")->where("user", $id)->fetch());
    }

    public function getWebsite(): ?string
    {
        return $this->getRecord()->website;
    }

    public function ban(string $reason): void
    {
        $this->setBlock_Reason($reason);
        $this->save();
    }

    public function delete(bool $softly = true): void
    {
        $this->ban("");
    }

    public function unban(): void
    {
        $this->setBlock_Reason(null);
        $this->save();
    }

    public function canBeViewedBy(?User $user = null)
    {
        return is_null($this->getBanReason());
    }

    public function getAlert(): ?string
    {
        return $this->getRecord()->alert;
    }

    public function getRealId(): int
    {
        return $this->getId() * -1;
    }

    public function isEveryoneCanUploadAudios(): bool
    {
        return (bool) $this->getRecord()->everyone_can_upload_audios;
    }

    public function canUploadAudio(?User $user): bool
    {
        if (!$user) {
            return null;
        }

        return $this->isEveryoneCanUploadAudios() || $this->canBeModifiedBy($user);
    }

    public function canUploadDocs(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->canBeModifiedBy($user);
    }

    public function getAudiosCollectionSize()
    {
        return (new \openvk\Web\Models\Repositories\Audios())->getClubCollectionSize($this);
    }

    public function toVkApiStruct(?User $user = null, string $fields = ''): object
    {
        $res = (object) [];

        $res->id          = $this->getId();
        $res->name        = $this->getName();
        $res->screen_name = $this->getShortCode() ?? "club" . $this->getId();
        $res->is_closed   = false;
        $res->type        = 'group';
        $res->is_member   = $user ? (int) $this->getSubscriptionStatus($user) : 0;
        $res->deactivated = null;
        $res->can_access_closed = true;

        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }

        $avatar_photo  = $this->getAvatarPhoto();
        foreach ($fields as $field) {
            switch ($field) {
                case 'verified':
                    $res->verified = (int) $this->isVerified();
                    break;
                case 'site':
                    $res->site = $this->getWebsite();
                    break;
                case 'description':
                    $res->description = $this->getDescription();
                    break;
                case 'background':
                    $res->background = $this->getBackDropPictureURLs();
                    break;
                case 'photo_50':
                    $res->photo_50 = $this->getAvatarUrl('miniscule', $avatar_photo);
                    break;
                case 'photo_100':
                    $res->photo_100 = $this->getAvatarUrl('tiny', $avatar_photo);
                    break;
                case 'photo_200':
                    $res->photo_200 = $this->getAvatarUrl('normal', $avatar_photo);
                    break;
                case 'photo_max':
                    $res->photo_max = $this->getAvatarUrl('original', $avatar_photo);
                    break;
                case 'members_count':
                    $res->members_count = $this->getFollowersCount();
                    break;
                case 'real_id':
                    $res->real_id = $this->getRealId();
                    break;
            }
        }

        return $res;
    }

    public function canCreateNote(?User $user): bool
    {
        return $this->canBeModifiedBy($user);
    }

    public function getMainNoteId(): ?int
    {
        if ($this->isWikiPagesDisabledEnforced()) {
            return null;
        }

        return $this->getRecord()->main_note_id;
    }

    public function getMainNote(): ?Note
    {
        return (new Notes())->get($this->getMainNoteId() ?? 0);
    }

    public function isMainNoteExpanded(): bool
    {
        return (bool) $this->getRecord()->is_main_note_expanded;
    }

    public function isMainNoteExpandedEnforced(): bool
    {
        return (bool) $this->getRecord()->enforce_main_note_expanded;
    }

    public function isWikiPagesDisabledEnforced(): bool
    {
        return (bool) $this->getRecord()->enforce_wiki_pages_disabled;
    }
}
