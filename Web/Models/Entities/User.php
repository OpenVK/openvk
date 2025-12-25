<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use morphos\Gender;
use openvk\Web\Themes\{Themepack, Themepacks};
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{Photo, Message, Correspondence, Gift, Audio};
use openvk\Web\Models\Repositories\{Applications, Bans, Comments, Notes, Posts, Users, Clubs, Albums, Gifts, Notifications, Videos, Photos};
use openvk\Web\Models\Exceptions\InvalidUserNameException;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

use function morphos\Russian\inflectName;

class User extends RowModel
{
    use Traits\TBackDrops;
    use Traits\TSubscribable;
    use Traits\TAudioStatuses;
    use Traits\TIgnorable;
    protected $tableName = "profiles";

    public const TYPE_DEFAULT = 0;
    public const TYPE_BOT     = 1;

    public const SUBSCRIPTION_ABSENT   = 0;
    public const SUBSCRIPTION_INCOMING = 1;
    public const SUBSCRIPTION_OUTGOING = 2;
    public const SUBSCRIPTION_MUTUAL   = 3;

    public const PRIVACY_NO_ONE          = 0;
    public const PRIVACY_ONLY_FRIENDS    = 1;
    public const PRIVACY_ONLY_REGISTERED = 2;
    public const PRIVACY_EVERYONE        = 3;

    public const NSFW_INTOLERANT    = 0;
    public const NSFW_TOLERANT      = 1;
    public const NSFW_FULL_TOLERANT = 2;

    protected function _abstractRelationGenerator(string $filename, int $page = 1, int $limit = 6): \Traversable
    {
        $id     = $this->getId();
        $query  = "SELECT id FROM\n" . file_get_contents(__DIR__ . "/../sql/$filename.tsql");
        $query .= "\n LIMIT " . $limit . " OFFSET " . (($page - 1) * $limit);

        $ids = [];
        $rels = DatabaseConnection::i()->getConnection()->query($query, $id, $id);
        foreach ($rels as $rel) {
            $rel = (new Users())->get($rel->id);
            if (!$rel) {
                continue;
            }
            if (in_array($rel->getId(), $ids)) {
                continue;
            }
            $ids[] = $rel->getId();

            yield $rel;
        }
    }

    protected function _abstractRelationCount(string $filename): int
    {
        $id    = $this->getId();
        $query = "SELECT COUNT(*) AS cnt FROM\n" . file_get_contents(__DIR__ . "/../sql/$filename.tsql");

        return (int) DatabaseConnection::i()->getConnection()->query($query, $id, $id)->fetch()->cnt;
    }

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getStyle(): string
    {
        return $this->getRecord()->style;
    }

    public function getTheme(): ?Themepack
    {
        return Themepacks::i()[$this->getStyle()] ?? null;
    }

    public function getStyleAvatar(): int
    {
        return $this->getRecord()->style_avatar;
    }

    public function hasMilkshakeEnabled(): bool
    {
        return (bool) $this->getRecord()->milkshake;
    }

    public function hasMicroblogEnabled(): bool
    {
        return (bool) $this->getRecord()->microblog;
    }

    public function getMainPage(): int
    {
        return $this->getRecord()->main_page;
    }

    public function getChandlerGUID(): string
    {
        return $this->getRecord()->user;
    }

    public function getChandlerUser(): ChandlerUser
    {
        return new ChandlerUser($this->getRecord()->ref("ChandlerUsers", "user"));
    }

    public function getURL(): string
    {
        if (!is_null($this->getShortCode())) {
            return "/" . $this->getShortCode();
        } else {
            return "/id" . $this->getId();
        }
    }

    public function getAvatarUrl(string $size = "miniscule", $avPhoto = null): string
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        if ($this->getRecord()->deleted) {
            return "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        } elseif ($this->isBanned()) {
            return "$serverUrl/assets/packages/static/openvk/img/banned.jpg";
        }

        if (!$avPhoto) {
            $avPhoto = $this->getAvatarPhoto();
        }

        if (is_null($avPhoto)) {
            return "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        } else {
            return $avPhoto->getURLBySizeId($size);
        }
    }

    public function getAvatarLink(): string
    {
        $avPhoto = $this->getAvatarPhoto();
        if (!$avPhoto) {
            return "javascript:void(0)";
        }

        $pid = $avPhoto->getPrettyId();
        $aid = (new Albums())->getUserAvatarAlbum($this)->getId();

        return "/photo$pid?from=album$aid";
    }

    public function getAvatarPhoto(): ?Photo
    {
        $avAlbum  = (new Albums())->getUserAvatarAlbum($this);
        $avCount  = $avAlbum->getPhotosCount();
        $avPhotos = $avAlbum->getPhotos($avCount, 1);

        return iterator_to_array($avPhotos)[0] ?? null;
    }

    public function getFirstName(bool $pristine = false): string
    {
        $name = ($this->isDeleted() && !$this->isDeactivated() ? "DELETED" : mb_convert_case($this->getRecord()->first_name, MB_CASE_TITLE));
        $tsn  = tr("__transNames");
        if (($tsn !== "@__transNames" && !empty($tsn)) && !$pristine) {
            return mb_convert_case(transliterator_transliterate($tsn, $name), MB_CASE_TITLE);
        } else {
            return $name;
        }
    }

    public function getLastName(bool $pristine = false): string
    {
        $name = ($this->isDeleted() && !$this->isDeactivated() ? "DELETED" : mb_convert_case($this->getRecord()->last_name, MB_CASE_TITLE));
        $tsn  = tr("__transNames");
        if (($tsn !== "@__transNames" && !empty($tsn)) && !$pristine) {
            return mb_convert_case(transliterator_transliterate($tsn, $name), MB_CASE_TITLE);
        } else {
            return $name;
        }
    }

    public function getPseudo(): ?string
    {
        return ($this->isDeleted() && !$this->isDeactivated() ? "DELETED" : $this->getRecord()->pseudo);
    }

    public function getFullName(): string
    {
        if ($this->isDeleted() && !$this->isDeactivated()) {
            return "DELETED";
        }

        $pseudo = $this->getPseudo();
        if (!$pseudo) {
            $pseudo = " ";
        } else {
            $pseudo = " ($pseudo) ";
        }

        $fullName = $this->getFirstName() . $pseudo . $this->getLastName();

        return strip_tags($fullName);
    }

    public function getMorphedName(string $case = "genitive", bool $fullName = true, bool $startWithLastName = true): string
    {
        if ($fullName) {
            if ($startWithLastName) {
                $name = $this->getLastName() . " " . $this->getFirstName();
            } else {
                $name = $this->getFirstName() . " " . $this->getLastName();
            }
        } else {
            $name = $this->getFirstName();
        }
        if (!preg_match("/^[А-Яа-яЁё\s-]+$/u", $name)) {
            return $name;
        } # name is probably not russian

        $inflected = inflectName($name, $case, $this->isFemale() ? Gender::FEMALE : Gender::MALE);

        return $inflected ?: $name;
    }

    public function getCanonicalName(): string
    {
        if ($this->isDeleted() && !$this->isDeactivated()) {
            return "DELETED";
        } else {
            return $this->getFirstName() . " " . $this->getLastName();
        }
    }

    public function getPhone(): ?string
    {
        return $this->getRecord()->phone;
    }

    public function getEmail(): ?string
    {
        return $this->getRecord()->email;
    }

    public function getOnline(): DateTime
    {
        return new DateTime($this->getRecord()->online);
    }

    public function getDescription(): ?string
    {
        return $this->getRecord()->about;
    }

    public function getAbout(): ?string
    {
        return $this->getRecord()->about;
    }

    public function getStatus(): ?string
    {
        return $this->getRecord()->status;
    }

    public function getShortCode(): ?string
    {
        return $this->getRecord()->shortcode;
    }

    public function getAlert(): ?string
    {
        return $this->getRecord()->alert;
    }

    public function getTextForContentBan(string $type): string
    {
        switch ($type) {
            case "post":    return "за размещение от Вашего лица таких <b>записей</b>:";
            case "photo":   return "за размещение от Вашего лица таких <b>фотографий</b>:";
            case "video":   return "за размещение от Вашего лица таких <b>видеозаписей</b>:";
            case "group":   return "за подозрительное вступление от Вашего лица <b>в группу:</b>";
            case "comment": return "за размещение от Вашего лица таких <b>комментариев</b>:";
            case "note":    return "за размещение от Вашего лица таких <b>заметок</b>:";
            case "app":     return "за создание от Вашего имени <b>подозрительных приложений</b>.";
            default:        return "за размещение от Вашего лица такого <b>контента</b>:";
        }
    }

    public function getRawBanReason(): ?string
    {
        return $this->getRecord()->block_reason;
    }

    public function getBanReason(?string $for = null)
    {
        $ban = (new Bans())->get((int) $this->getRecord()->block_reason);
        if (!$ban || $ban->isOver()) {
            return null;
        }

        $reason = $ban->getReason();

        preg_match('/\*\*content-(post|photo|video|group|comment|note|app|noSpamTemplate|user)-(\d+)\*\*$/', $reason, $matches);
        if (sizeof($matches) === 3) {
            $content_type = $matches[1];
            $content_id = (int) $matches[2];
            if (in_array($content_type, ["noSpamTemplate", "user"])) {
                $reason = $this->getRawBanReason();
            } else {
                if ($for !== "banned") {
                    $reason = $this->getRawBanReason();
                } else {
                    $reason = [$this->getTextForContentBan($content_type), $content_type];
                    switch ($content_type) {
                        case "post":    $reason[] = (new Posts())->get($content_id);
                            break;
                        case "photo":   $reason[] = (new Photos())->get($content_id);
                            break;
                        case "video":   $reason[] = (new Videos())->get($content_id);
                            break;
                        case "group":   $reason[] = (new Clubs())->get($content_id);
                            break;
                        case "comment": $reason[] = (new Comments())->get($content_id);
                            break;
                        case "note":    $reason[] = (new Notes())->get($content_id);
                            break;
                        case "app":     $reason[] = (new Applications())->get($content_id);
                            break;
                        case "user":    $reason[] = (new Users())->get($content_id);
                            break;
                        default:        $reason[] = null;
                    }
                }
            }
        }

        return $reason;
    }

    public function getBanInSupportReason(): ?string
    {
        return $this->getRecord()->block_in_support_reason;
    }

    public function getType(): int
    {
        return $this->getRecord()->type;
    }

    public function getCoins(): float
    {
        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"]) {
            return 0.0;
        }

        return $this->getRecord()->coins;
    }

    public function getRating(): int
    {
        return OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"] ? $this->getRecord()->rating : 0;
    }

    public function getReputation(): int
    {
        return $this->getRecord()->reputation;
    }

    public function getRegistrationTime(): DateTime
    {
        return new DateTime($this->getRecord()->since->getTimestamp());
    }

    public function getRegistrationIP(): string
    {
        return $this->getRecord()->registering_ip;
    }

    public function getHometown(): ?string
    {
        return $this->getRecord()->hometown;
    }

    public function getPoliticalViews(): int
    {
        return $this->getRecord()->polit_views;
    }

    public function getMaritalStatus(): int
    {
        return $this->getRecord()->marital_status;
    }

    public function getLocalizedMaritalStatus(?bool $prefix = false): string
    {
        $status = $this->getMaritalStatus();
        $string = "relationship_$status";
        if ($prefix) {
            $string .= "_prefix";
        }
        if ($this->isFemale()) {
            $res = tr($string . "_fem");
            if ($res != ("@" . $string . "_fem")) {
                return $res;
            } # If fem version exists, return
        }

        return tr($string);
    }

    public function getMaritalStatusUser(): ?User
    {
        if (!$this->getRecord()->marital_status_user) {
            return null;
        }
        return (new Users())->get($this->getRecord()->marital_status_user);
    }

    public function getMaritalStatusUserPrefix(): ?string
    {
        return $this->getLocalizedMaritalStatus(true);
    }

    public function getContactEmail(): ?string
    {
        return $this->getRecord()->email_contact;
    }

    public function getTelegram(): ?string
    {
        return $this->getRecord()->telegram;
    }

    public function getInterests(): ?string
    {
        return $this->getRecord()->interests;
    }

    public function getFavoriteMusic(): ?string
    {
        return $this->getRecord()->fav_music;
    }

    public function getFavoriteFilms(): ?string
    {
        return $this->getRecord()->fav_films;
    }

    public function getFavoriteShows(): ?string
    {
        return $this->getRecord()->fav_shows;
    }

    public function getFavoriteBooks(): ?string
    {
        return $this->getRecord()->fav_books;
    }

    public function getFavoriteQuote(): ?string
    {
        return $this->getRecord()->fav_quote;
    }

    public function getFavoriteGames(): ?string
    {
        return $this->getRecord()->fav_games;
    }

    public function getCity(): ?string
    {
        return $this->getRecord()->city;
    }

    public function getPhysicalAddress(): ?string
    {
        return $this->getRecord()->address;
    }

    public function getAdditionalFields(bool $split = false): array
    {
        $all = \openvk\Web\Models\Entities\UserInfoEntities\AdditionalField::getByOwner($this->getId());
        $result = [
            "interests" => [],
            "contacts"  => [],
        ];

        if ($split) {
            foreach ($all as $field) {
                if ($field->getPlace() == "contact") {
                    $result["contacts"][] = $field;
                } elseif ($field->getPlace() == "interest") {
                    $result["interests"][] = $field;
                }
            }
        } else {
            $result = [];
            foreach ($all as $field) {
                $result[] = $field;
            }
        }

        return $result;
    }

    public function getNotificationOffset(): int
    {
        return $this->getRecord()->notification_offset;
    }

    public function getBirthday(): ?DateTime
    {
        if (is_null($this->getRecord()->birthday)) {
            return null;
        } else {
            return new DateTime($this->getRecord()->birthday);
        }
    }

    public function getBirthdayPrivacy(): int
    {
        return $this->getRecord()->birthday_privacy;
    }

    public function getAge(): ?int
    {
        $birthday = new \DateTime();
        $birthday->setTimestamp($this->getBirthday()->timestamp());
        $today = new \DateTime();
        return (int) $today->diff($birthday)->y;
    }

    public function get2faSecret(): ?string
    {
        return $this->getRecord()["2fa_secret"];
    }

    public function is2faEnabled(): bool
    {
        return !is_null($this->get2faSecret());
    }

    public function updateNotificationOffset(): void
    {
        $this->stateChanges("notification_offset", time());
    }

    public function getLeftMenuItemStatus(string $id): bool
    {
        return (bool) bmask($this->getRecord()->left_menu, [
            "length"   => 1,
            "mappings" => [
                "photos",
                "audios",
                "videos",
                "messages",
                "notes",
                "groups",
                "news",
                "links",
                "poster",
                "apps",
                "docs",
                "fave",
            ],
        ])->get($id);
    }

    public function getPrivacySetting(string $id): int
    {
        return (int) bmask($this->getRecord()->privacy, [
            "length"   => 2,
            "mappings" => [
                "page.read",
                "page.info.read",
                "groups.read",
                "photos.read",
                "videos.read",
                "notes.read",
                "friends.read",
                "friends.add",
                "wall.write",
                "messages.write",
                "audios.read",
                "likes.read",
            ],
        ])->get($id);
    }

    public function getPrivacyPermission(string $permission, ?User $user = null): bool
    {
        $permStatus = $this->getPrivacySetting($permission);
        if (!$user) {
            return $permStatus === User::PRIVACY_EVERYONE;
        } elseif ($user->getId() === $this->getId()) {
            return true;
        }

        if (/*$permission != "messages.write" && */!$this->canBeViewedBy($user, true)) {
            return false;
        }

        switch ($permStatus) {
            case User::PRIVACY_ONLY_FRIENDS:
                return $this->getSubscriptionStatus($user) === User::SUBSCRIPTION_MUTUAL;
            case User::PRIVACY_ONLY_REGISTERED:
            case User::PRIVACY_EVERYONE:
                return true;
            default:
                return false;
        }
    }

    public function getProfileCompletenessReport(): object
    {
        $incompleteness = 0;
        $unfilled       = [];

        if (!$this->getRecord()->status) {
            $unfilled[]      = "status";
            $incompleteness += 15;
        }
        if (!$this->getRecord()->telegram) {
            $unfilled[]      = "telegram";
            $incompleteness += 15;
        }
        if (!$this->getRecord()->email) {
            $unfilled[]      = "email";
            $incompleteness += 20;
        }
        if (!$this->getRecord()->city) {
            $unfilled[]      = "city";
            $incompleteness += 20;
        }
        if (!$this->getRecord()->interests) {
            $unfilled[]      = "interests";
            $incompleteness += 20;
        }

        $total = max(100 - $incompleteness + $this->getRating(), 0);
        if (ovkGetQuirk("profile.rating-bar-behaviour") === 0) {
            if ($total >= 100) {
                $percent = round(($total / 10 ** strlen(strval($total))) * 100, 0);
            } else {
                $percent = min($total, 100);
            }
        }

        return (object) [
            "total"    => $total,
            "percent"  => $percent,
            "unfilled" => $unfilled,
        ];
    }

    public function getFriends(int $page = 1, int $limit = 6): \Traversable
    {
        return $this->_abstractRelationGenerator("get-friends", $page, $limit);
    }

    public function getFriendsCount(): int
    {
        return $this->_abstractRelationCount("get-friends");
    }

    public function getFriendsOnline(int $page = 1, int $limit = 6): \Traversable
    {
        return $this->_abstractRelationGenerator("get-online-friends", $page, $limit);
    }

    public function getFriendsOnlineCount(): int
    {
        return $this->_abstractRelationCount("get-online-friends");
    }

    public function getFriendsBday(bool $today): array
    {
        $users = $this->_abstractRelationGenerator($today ? "get-bday-today" : "get-bday-tomorrow", 1, 3000);
        $usersFiltered = [];
        foreach ($users as $u) {
            if ($u->getPrivacySetting("page.info.read") != 0) {
                $usersFiltered[] = $u;
            }
        }

        if (sizeof($usersFiltered) > 0) {
            return [
                "isToday" => $today,
                "users" => $usersFiltered,
            ];
        }
        return [];
    }

    public function getFollowers(int $page = 1, int $limit = 6): \Traversable
    {
        return $this->_abstractRelationGenerator("get-followers", $page, $limit);
    }

    public function getFollowersCount(): int
    {
        return $this->_abstractRelationCount("get-followers");
    }

    public function getRequests(int $page = 1, int $limit = 6): \Traversable
    {
        return $this->_abstractRelationGenerator("get-requests", $page, $limit);
    }

    public function getRequestsCount(): int
    {
        return $this->_abstractRelationCount("get-requests");
    }

    public function getSubscriptions(int $page = 1, int $limit = 6): \Traversable
    {
        return $this->_abstractRelationGenerator("get-subscriptions-user", $page, $limit);
    }

    public function getSubscriptionsCount(): int
    {
        return $this->_abstractRelationCount("get-subscriptions-user");
    }

    public function getUnreadMessagesCount(): int
    {
        return sizeof(DatabaseConnection::i()->getContext()->table("messages")->where(["recipient_id" => $this->getId(), "unread" => 1]));
    }

    public function getClubs(int $page = 1, bool $admin = false, int $count = OPENVK_DEFAULT_PER_PAGE, bool $offset = false): \Traversable
    {
        if (!$offset) {
            $page = ($page - 1) * $count;
        }

        if ($admin) {
            $id     = $this->getId();
            $query  = "SELECT `id` FROM `groups` WHERE `owner` = ? UNION SELECT `club` as `id` FROM `group_coadmins` WHERE `user` = ?";
            $query .= " LIMIT " . $count . " OFFSET " . $page;

            $sel = DatabaseConnection::i()->getConnection()->query($query, $id, $id);
            foreach ($sel as $target) {
                $target = (new Clubs())->get($target->id);
                if (!$target) {
                    continue;
                }

                yield $target;
            }
        } else {
            $sel = $this->getRecord()->related("subscriptions.follower")->limit($count, $page);
            foreach ($sel->where("model", "openvk\\Web\\Models\\Entities\\Club") as $target) {
                $target = (new Clubs())->get($target->target);
                if (!$target) {
                    continue;
                }

                yield $target;
            }
        }
    }

    public function getClubCount(bool $admin = false): int
    {
        if ($admin) {
            $id    = $this->getId();
            $query = "SELECT COUNT(*) AS `cnt` FROM (SELECT `id` FROM `groups` WHERE `owner` = ? UNION SELECT `club` as `id` FROM `group_coadmins` WHERE `user` = ?) u0;";

            return (int) DatabaseConnection::i()->getConnection()->query($query, $id, $id)->fetch()->cnt;
        } else {
            $sel = $this->getRecord()->related("subscriptions.follower");
            $sel = $sel->where("model", "openvk\\Web\\Models\\Entities\\Club");

            return sizeof($sel);
        }
    }

    public function getPinnedClubs(): \Traversable
    {
        foreach ($this->getRecord()->related("groups.owner")->where("owner_club_pinned", true) as $target) {
            $target = (new Clubs())->get($target->id);
            if (!$target) {
                continue;
            }

            yield $target;
        }

        foreach ($this->getRecord()->related("group_coadmins.user")->where("club_pinned", true) as $target) {
            $target = (new Clubs())->get($target->club);
            if (!$target) {
                continue;
            }

            yield $target;
        }
    }

    public function getPinnedClubCount(): int
    {
        return sizeof($this->getRecord()->related("groups.owner")->where("owner_club_pinned", true)) + sizeof($this->getRecord()->related("group_coadmins.user")->where("club_pinned", true));
    }

    public function isClubPinned(Club $club): bool
    {
        if ($club->getOwner()->getId() === $this->getId()) {
            return $club->isOwnerClubPinned();
        }

        $manager = $club->getManager($this);
        if (!is_null($manager)) {
            return $manager->isClubPinned();
        }

        return false;
    }

    public function getMeetings(int $page = 1): \Traversable
    {
        $sel = $this->getRecord()->related("event_turnouts.user")->page($page, OPENVK_DEFAULT_PER_PAGE);
        foreach ($sel as $target) {
            $target = (new Clubs())->get($target->event);
            if (!$target) {
                continue;
            }

            yield $target;
        }
    }

    public function getMeetingCount(): int
    {
        return sizeof($this->getRecord()->related("event_turnouts.user"));
    }

    public function getGifts(int $page = 1, ?int $perPage = null): \Traversable
    {
        $gifts = $this->getRecord()->related("gift_user_relations.receiver")->order("sent DESC")->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        foreach ($gifts as $rel) {
            yield (object) [
                "sender"  => (new Users())->get($rel->sender),
                "gift"    => (new Gifts())->get($rel->gift),
                "caption" => $rel->comment,
                "anon"    => $rel->anonymous,
                "sent"    => new DateTime($rel->sent),
            ];
        }
    }

    public function getGiftCount(): int
    {
        return sizeof($this->getRecord()->related("gift_user_relations.receiver"));
    }

    public function get2faBackupCodes(): \Traversable
    {
        $sel = $this->getRecord()->related("2fa_backup_codes.owner");
        foreach ($sel as $target) {
            yield $target->code;
        }
    }

    public function get2faBackupCodeCount(): int
    {
        return sizeof($this->getRecord()->related("2fa_backup_codes.owner"));
    }

    public function generate2faBackupCodes(): void
    {
        $codes = [];

        for ($i = 0; $i < 10 - $this->get2faBackupCodeCount(); $i++) {
            $codes[] = [
                "owner" => $this->getId(),
                "code" => random_int(10000000, 99999999),
            ];
        }

        if (sizeof($codes) > 0) {
            DatabaseConnection::i()->getContext()->table("2fa_backup_codes")->insert($codes);
        }
    }

    public function use2faBackupCode(int $code): bool
    {
        return (bool) $this->getRecord()->related("2fa_backup_codes.owner")->where("code", $code)->delete();
    }

    public function getSubscriptionStatus(User $user): int
    {
        $subbed = !is_null($this->getRecord()->related("subscriptions.follower")->where([
            "model"  => static::class,
            "target" => $user->getId(),
        ])->fetch());
        $followed = !is_null($this->getRecord()->related("subscriptions.target")->where([
            "model"    => static::class,
            "follower" => $user->getId(),
        ])->fetch());

        if ($subbed && $followed) {
            return User::SUBSCRIPTION_MUTUAL;
        }
        if ($subbed) {
            return User::SUBSCRIPTION_INCOMING;
        }
        if ($followed) {
            return User::SUBSCRIPTION_OUTGOING;
        }

        return User::SUBSCRIPTION_ABSENT;
    }

    public function getNotificationsCount(bool $archived = false): int
    {
        return (new Notifications())->getNotificationCountByUser($this, $this->getNotificationOffset(), $archived);
    }

    public function getNotifications(int $page, bool $archived = false): \Traversable
    {
        return (new Notifications())->getNotificationsByUser($this, $this->getNotificationOffset(), $archived, $page);
    }

    public function getPendingPhoneVerification(): ?ActiveRow
    {
        return $this->getRecord()->ref("number_verification", "id");
    }

    public function getRefLinkId(): string
    {
        $hash = hash_hmac("Snefru", (string) $this->getId(), CHANDLER_ROOT_CONF["security"]["secret"], true);

        return dechex($this->getId()) . " " . base64_encode($hash);
    }

    public function getNsfwTolerance(): int
    {
        return $this->getRecord()->nsfw_tolerance;
    }

    public function isFemale(): bool
    {
        return $this->getRecord()->sex == 1;
    }

    public function isNeutral(): bool
    {
        return (bool) $this->getRecord()->sex == 2;
    }

    public function getLocalizedPronouns(): string
    {
        switch ($this->getRecord()->sex) {
            case 0:
                return tr('male');
            case 1:
                return tr('female');
            case 2:
            default:
                return tr('neutral');
        }
    }

    public function getPronouns(): int
    {
        return $this->getRecord()->sex;
    }

    public function isVerified(): bool
    {
        return (bool) $this->getRecord()->verified;
    }

    public function isBanned(): bool
    {
        return !is_null($this->getBanReason());
    }

    public function isBannedInSupport(): bool
    {
        return !is_null($this->getBanInSupportReason());
    }

    public function isOnline(): bool
    {
        return time() - $this->getRecord()->online <= 300;
    }

    public function getOnlinePlatform(bool $forAPI = false): ?string
    {
        $platform = $this->getRecord()->client_name;
        if ($forAPI) {
            switch ($platform) {
                case 'openvk_native':
                case 'openvk_refresh_android':
                case 'openvk_legacy_android':
                    return 'android';
                    break;

                case 'openvk_native_ios':
                case 'openvk_ios':
                case 'openvk_legacy_ios':
                    return 'iphone';
                    break;

                case 'vika_touch': // кика хохотач ахахахаххахахахахах
                case 'vk4me':
                    return 'mobile';
                    break;

                case null:
                    return null;
                    break;

                default:
                    return 'api';
                    break;
            }
        } else {
            return $platform;
        }
    }

    public function getOnlinePlatformDetails(): array
    {
        $clients = simplexml_load_file(OPENVK_ROOT . "/data/clients.xml");

        foreach ($clients as $client) {
            if ($client['tag'] == $this->getOnlinePlatform()) {
                return [
                    "tag"  => $client['tag'],
                    "name" => $client['name'],
                    "url"  => $client['url'],
                    "img"  => $client['img'],
                ];
                break;
            }
        }

        return [
            "tag"  => $this->getOnlinePlatform(),
            "name" => null,
            "url"  => null,
            "img"  => null,
        ];
    }

    public function prefersNotToSeeRating(): bool
    {
        return !((bool) $this->getRecord()->show_rating);
    }

    public function hasPendingNumberChange(): bool
    {
        return !is_null($this->getPendingPhoneVerification());
    }

    public function gift(User $sender, Gift $gift, ?string $comment = null, bool $anonymous = false): void
    {
        DatabaseConnection::i()->getContext()->table("gift_user_relations")->insert([
            "sender"    => $sender->getId(),
            "receiver"  => $this->getId(),
            "gift"      => $gift->getId(),
            "comment"   => $comment,
            "anonymous" => $anonymous,
            "sent"      => time(),
        ]);
    }

    public function ban(string $reason, bool $deleteSubscriptions = true, $unban_time = null, ?int $initiator = null): void
    {
        if ($deleteSubscriptions) {
            $subs = DatabaseConnection::i()->getContext()->table("subscriptions");
            $subs = $subs->where(
                "follower = ? OR (target = ? AND model = ?)",
                $this->getId(),
                $this->getId(),
                get_class($this),
            );
            $subs->delete();
        }

        $iat = time();
        $ban = new Ban();
        $ban->setUser($this->getId());
        $ban->setReason($reason);
        $ban->setInitiator($initiator);
        $ban->setIat($iat);
        $ban->setExp($unban_time !== "permanent" ? $unban_time : 0);
        $ban->setTime($unban_time === "permanent" ? 0 : ($unban_time ? ($unban_time - $iat) : 0));
        $ban->save();

        $this->setBlock_Reason($ban->getId());
        // $this->setUnblock_time($unban_time);
        $this->save();
    }

    public function unban(int $removed_by): void
    {
        $ban = (new Bans())->get((int) $this->getRawBanReason());
        if (!$ban || $ban->isOver()) {
            return;
        }

        $ban->setRemoved_Manually(true);
        $ban->setRemoved_By($removed_by);
        $ban->save();

        $this->setBlock_Reason(null);
        // $user->setUnblock_time(NULL);
        $this->save();
    }

    public function deactivate(?string $reason): void
    {
        $this->setDeleted(1);
        $this->setDeact_Date(time() + (MONTH * 7));
        $this->setDeact_Reason($reason);
        $this->save();
    }

    public function reactivate(): void
    {
        $this->setDeleted(0);
        $this->setDeact_Date(0);
        $this->setDeact_Reason("");
        $this->save();
    }

    public function getDeactivationDate(): DateTime
    {
        return new DateTime($this->getRecord()->deact_date);
    }

    public function verifyNumber(string $code): bool
    {
        $ver = $this->getPendingPhoneVerification();
        if (!$ver) {
            return false;
        }

        try {
            if (sodium_memcmp((string) $ver->code, $code) === -1) {
                return false;
            }
        } catch (\SodiumException $ex) {
            return false;
        }

        $this->setPhone($ver->number);
        $this->save();

        DatabaseConnection::i()->getContext()
                               ->table("number_verification")
                               ->where("user", $this->getId())
                               ->delete();

        return true;
    }

    public function setFirst_Name(string $firstName): void
    {
        $firstName = mb_convert_case($firstName, MB_CASE_TITLE);
        if (!preg_match('%^[\p{Lu}\p{Lo}]\p{Mn}?(?:[\p{L&}\p{Lo}]\p{Mn}?){1,64}$%u', $firstName)) {
            throw new InvalidUserNameException();
        }

        $this->stateChanges("first_name", $firstName);
    }

    public function setLast_Name(string $lastName): void
    {
        if (!empty($lastName)) {
            $lastName = mb_convert_case($lastName, MB_CASE_TITLE);
            if (!preg_match('%^[\p{Lu}\p{Lo}]\p{Mn}?([\p{L&}\p{Lo}]\p{Mn}?){1,64}(\-\g<1>+)?$%u', $lastName)) {
                throw new InvalidUserNameException();
            }
        }

        $this->stateChanges("last_name", $lastName);
    }

    public function setNsfwTolerance(int $tolerance): void
    {
        $this->stateChanges("nsfw_tolerance", $tolerance);
    }

    public function setPrivacySetting(string $id, int $status): void
    {
        $this->stateChanges("privacy", bmask($this->changes["privacy"] ?? $this->getRecord()->privacy, [
            "length"   => 2,
            "mappings" => [
                "page.read",
                "page.info.read",
                "groups.read",
                "photos.read",
                "videos.read",
                "notes.read",
                "friends.read",
                "friends.add",
                "wall.write",
                "messages.write",
                "audios.read",
                "likes.read",
            ],
        ])->set($id, $status)->toInteger());
    }

    public function setLeftMenuItemStatus(string $id, bool $status): void
    {
        $mask = bmask($this->changes["left_menu"] ?? $this->getRecord()->left_menu, [
            "length"   => 1,
            "mappings" => [
                "photos",
                "audios",
                "videos",
                "messages",
                "notes",
                "groups",
                "news",
                "links",
                "poster",
                "apps",
                "docs",
                "fave",
            ],
        ])->set($id, (int) $status)->toInteger();

        $this->stateChanges("left_menu", $mask);
    }

    public function setShortCode(?string $code = null, bool $force = false): ?bool
    {
        if (!is_null($code)) {
            if (strlen($code) < OPENVK_ROOT_CONF["openvk"]["preferences"]["shortcodes"]["minLength"] && !$force) {
                return false;
            }
            if (!preg_match("%^[a-z][a-z0-9\\.\\_]{0,30}[a-z0-9]$%", $code)) {
                return false;
            }
            if (in_array($code, OPENVK_ROOT_CONF["openvk"]["preferences"]["shortcodes"]["forbiddenNames"])) {
                return false;
            }
            if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$code")[0]->presenter !== "UnknownTextRouteStrategy") {
                return false;
            }

            $pClub = DatabaseConnection::i()->getContext()->table("groups")->where("shortcode", $code)->fetch();
            if (!is_null($pClub)) {
                return false;
            }

            $pAlias = DatabaseConnection::i()->getContext()->table("aliases")->where("shortcode", $code)->fetch();
            if (!is_null($pAlias)) {
                return false;
            }
        }

        $this->stateChanges("shortcode", $code);
        return true;
    }

    public function setPhoneWithVerification(string $phone): string
    {
        $code = unpack("S", openssl_random_pseudo_bytes(2))[1];

        if ($this->hasPendingNumberChange()) {
            DatabaseConnection::i()->getContext()
                                   ->table("number_verification")
                                   ->where("user", $this->getId())
                                   ->update(["number" => $phone, "code" => $code]);
        } else {
            DatabaseConnection::i()->getContext()
                                   ->table("number_verification")
                                   ->insert(["user" => $this->getId(), "number" => $phone, "code" => $code]);
        }

        return (string) $code;
    }

    # KABOBSQL temporary fix
    # Tuesday, the 7th of January 2020 @ 22:43 <Menhera>: implementing quick fix to this problem and monitoring
    # NOTICE: this is an ongoing conversation, add your comments just above this line. Thanks!
    public function setOnline(int $time): bool
    {
        $this->stateChanges("shortcode", $this->getRecord()->shortcode); #fix KABOBSQL
        $this->stateChanges("online", $time);

        return true;
    }

    public function updOnline(string $platform): bool
    {
        $this->setOnline(time());
        $this->setClient_name($platform);
        $this->save(false);

        return true;
    }

    public function changeEmail(string $email): void
    {
        DatabaseConnection::i()->getContext()->table("ChandlerUsers")
            ->where("id", $this->getChandlerUser()->getId())->update([
                "login" => $email,
            ]);

        $this->stateChanges("email", $email);
        $this->save();
    }

    public function adminNotify(string $message): bool
    {
        $admId = (int) OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["adminAccount"];
        if (!$admId) {
            return false;
        } elseif (is_null($admin = (new Users())->get($admId))) {
            return false;
        }

        $cor = new Correspondence($admin, $this);
        $msg = new Message();
        $msg->setContent($message);
        $cor->sendMessage($msg, true);

        return true;
    }

    public function isDeleted(): bool
    {
        if ($this->getRecord()->deleted == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function isDeactivated(): bool
    {
        if ($this->getDeactivationDate()->timestamp() > time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 0 - Default status
     * 1 - Incognito online status
     * 2 - Page of a dead person
     */
    public function onlineStatus(): int
    {
        switch ($this->getRecord()->online) {
            case 1:
                return 1;
                break;

            case 2:
                return 2;
                break;

            default:
                return 0;
                break;
        }
    }

    public function getWebsite(): ?string
    {
        return $this->getRecord()->website;
    }

    # ты устрица
    public function isActivated(): bool
    {
        return (bool) $this->getRecord()->activated;
    }

    public function isAdmin(): bool
    {
        return $this->getChandlerUser()->can("access")->model("admin")->whichBelongsTo(null);
    }

    public function isDead(): bool
    {
        return $this->onlineStatus() == 2;
    }

    public function getUnbanTime(): ?string
    {
        $ban = (new Bans())->get((int) $this->getRecord()->block_reason);
        if (!$ban || $ban->isOver() || $ban->isPermanent()) {
            return null;
        }
        if ($this->canUnbanThemself()) {
            return tr("today");
        }

        return date('d.m.Y', $ban->getEndTime());
    }

    public function canUnbanThemself(): bool
    {
        if (!$this->isBanned()) {
            return false;
        }

        $ban = (new Bans())->get((int) $this->getRecord()->block_reason);
        if (!$ban || $ban->isOver() || $ban->isPermanent()) {
            return false;
        }

        return $ban->getEndTime() <= time() && !$ban->isPermanent();
    }

    public function getNewBanTime()
    {
        $bans = iterator_to_array((new Bans())->getByUser($this->getid()));
        if (!$bans || count($bans) === 0) {
            return 0;
        }

        $last_ban = end($bans);
        if (!$last_ban) {
            return 0;
        }

        if ($last_ban->isPermanent()) {
            return "0";
        }

        $values = [0, 3600, 7200, 86400, 172800, 604800, 1209600, 3024000, 9072000];
        $response = 0;
        $i = 0;

        foreach ($values as $value) {
            $i++;
            if ($last_ban->getTime() === 0 && $value === 0) {
                continue;
            }
            if ($last_ban->getTime() < $value) {
                $response = $value;
                break;
            } elseif ($last_ban->getTime() >= $value) {
                if ($i < count($values)) {
                    continue;
                }
                $response = "0";
                break;
            }
        }
        return $response;
    }

    public function getProfileType(): int
    {
        # 0 — открытый профиль, 1 — закрытый
        return $this->getRecord()->profile_type;
    }

    public function canBeViewedBy(?User $user = null, bool $blacklist_check = true): bool
    {
        if (!is_null($user)) {
            if ($this->getId() == $user->getId()) {
                return true;
            }

            if ($user->isAdmin() && !(OPENVK_ROOT_CONF['openvk']['preferences']['blacklists']['applyToAdmins'] ?? true)) {
                return true;
            }

            if ($blacklist_check && ($this->isBlacklistedBy($user) || $user->isBlacklistedBy($this))) {
                return false;
            }

            if ($this->getProfileType() == 0) {
                return true;
            } else {
                if ($user->getSubscriptionStatus($this) == User::SUBSCRIPTION_MUTUAL) {
                    return true;
                } else {
                    return false;
                }
            }

        } else {
            if ($this->getProfileType() == 0) {
                if ($this->getPrivacySetting("page.read") == 3) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function isClosed(): bool
    {
        return (bool) $this->getProfileType();
    }

    public function isHideFromGlobalFeedEnabled(): bool
    {
        return $this->isClosed();
    }

    public function HideGlobalFeed(): bool
    {
        return (bool) $this->getRecord()->hide_global_feed;
    }

    public function getRealId()
    {
        return $this->getId();
    }

    public function isPrivateLikes(): bool
    {
        return $this->getPrivacySetting("likes.read") == User::PRIVACY_NO_ONE;
    }

    public function toVkApiStruct(?User $relation_user = null, string $fields = ''): object
    {
        $res = (object) [];

        $res->id = $this->getId();
        $res->first_name = $this->getFirstName();
        $res->last_name = $this->getLastName();
        $res->deactivated = $this->isDeactivated();
        $res->is_closed   = $this->isClosed();

        if (!is_null($relation_user)) {
            $res->can_access_closed  = (bool) $this->canBeViewedBy($relation_user);
        }

        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }

        $avatar_photo  = $this->getAvatarPhoto();
        foreach ($fields as $field) {
            switch ($field) {
                case 'is_dead':
                    $res->is_dead = $this->isDead();
                    break;
                case 'verified':
                    $res->verified = (int) $this->isVerified();
                    break;
                case 'sex':
                    $res->sex = $this->isFemale() ? 1 : ($this->isNeutral() ? 0 : 2);
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
                case 'photo_id':
                    $res->photo_id = $avatar_photo ? $avatar_photo->getPrettyId() : null;
                    break;
                case 'background':
                    $res->background = $this->getBackDropPictureURLs();
                    break;
                case 'reg_date':
                    $res->reg_date = $this->getRegistrationTime()->timestamp();
                    break;
                case 'nickname':
                    $res->nickname = $this->getPseudo();
                    break;
                case 'rating':
                    $res->rating = $this->getRating();
                    break;
                case 'status':
                    $res->status = $this->getStatus();
                    break;
                case 'screen_name':
                    $res->screen_name = $this->getShortCode() ?? "id" . $this->getId();
                    break;
                case 'real_id':
                    $res->real_id = $this->getRealId();
                    break;
                case "blacklisted_by_me":
                    if (!$relation_user) {
                        break;
                    }

                    $res->blacklisted_by_me = (int) $this->isBlacklistedBy($relation_user);
                    break;
                case "blacklisted":
                    if (!$relation_user) {
                        break;
                    }

                    $res->blacklisted = (int) $relation_user->isBlacklistedBy($this);
                    break;
                case "games":
                    $res->games = $this->getFavoriteGames();
                    break;
            }
        }

        return $res;
    }

    public function getAudiosCollectionSize()
    {
        return (new \openvk\Web\Models\Repositories\Audios())->getUserCollectionSize($this);
    }

    public function getBroadcastList(string $filter = "friends", bool $shuffle = false)
    {
        $dbContext = DatabaseConnection::i()->getContext();
        $entityIds = [];
        $query     = $dbContext->table("subscriptions")->where("follower", $this->getRealId());

        if ($filter != "all") {
            $query = $query->where("model = ?", "openvk\\Web\\Models\\Entities\\" . ($filter == "groups" ? "Club" : "User"));
        }

        foreach ($query as $_rel) {
            $entityIds[] = $_rel->model == "openvk\\Web\\Models\\Entities\\Club" ? $_rel->target * -1 : $_rel->target;
        }

        if ($shuffle) {
            $shuffleSeed    = openssl_random_pseudo_bytes(6);
            $shuffleSeed    = hexdec(bin2hex($shuffleSeed));

            $entityIds = knuth_shuffle($entityIds, $shuffleSeed);
        }

        $entityIds = array_slice($entityIds, 0, 10);

        $returnArr = [];

        foreach ($entityIds as $id) {
            $entit = $id > 0 ? (new Users())->get($id) : (new Clubs())->get(abs($id));

            if ($id > 0 && $entit->isDeleted()) {
                continue;
            }
            $returnArr[] = $entit;
        }

        return $returnArr;
    }

    public function getIgnoredSources(int $offset = 0, int $limit = 10, bool $onlyIds = false)
    {
        $sources = DatabaseConnection::i()->getContext()->table("ignored_sources")->where("owner", $this->getId())->limit($limit, $offset)->order('id DESC');
        $output_array = [];

        foreach ($sources as $source) {
            if ($onlyIds) {
                $output_array[] = (int) $source->source;
            } else {
                $ignored_source_model = null;
                $ignored_source_id = (int) $source->source;

                if ($ignored_source_id > 0) {
                    $ignored_source_model = (new Users())->get($ignored_source_id);
                } else {
                    $ignored_source_model = (new Clubs())->get(abs($ignored_source_id));
                }

                if (!$ignored_source_model) {
                    continue;
                }

                $output_array[] = $ignored_source_model;
            }
        }

        return $output_array;
    }

    public function getIgnoredSourcesCount()
    {
        return DatabaseConnection::i()->getContext()->table("ignored_sources")->where("owner", $this->getId())->count('*');
    }

    public function isBlacklistedBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        $ctx  = DatabaseConnection::i()->getContext();
        $data = [
            "author" => $user->getId(),
            "target" => $this->getRealId(),
        ];

        $sub = $ctx->table("blacklist_relations")->where($data);
        return $sub->count('*') > 0;
    }

    public function addToBlacklist(?User $user)
    {
        DatabaseConnection::i()->getContext()->table("blacklist_relations")->insert([
            "author"  => $this->getRealId(),
            "target"  => $user->getRealId(),
            "created" => time(),
        ]);

        DatabaseConnection::i()->getContext()->table("subscriptions")->where([
            "follower" => $user->getId(),
            "model"    => static::class,
            "target"   => $this->getId(),
        ])->delete();

        DatabaseConnection::i()->getContext()->table("subscriptions")->where([
            "follower" => $this->getId(),
            "model"    => static::class,
            "target"   => $user->getId(),
        ])->delete();

        return true;
    }

    public function removeFromBlacklist(?User $user): bool
    {
        DatabaseConnection::i()->getContext()->table("blacklist_relations")->where([
            "author" => $this->getRealId(),
            "target" => $user->getRealId(),
        ])->delete();

        return true;
    }

    public function getBlacklist(int $offset = 0, int $limit = 10)
    {
        $sources = DatabaseConnection::i()->getContext()->table("blacklist_relations")->where("author", $this->getId())->limit($limit, $offset)->order('created ASC');
        $output_array = [];

        foreach ($sources as $source) {
            $entity_id = (int) $source->target ;
            $entity = (new Users())->get($entity_id);
            if (!$entity) {
                continue;
            }

            $output_array[] = $entity;
        }

        return $output_array;
    }

    public function getBlacklistSize()
    {
        return DatabaseConnection::i()->getContext()->table("blacklist_relations")->where("author", $this->getId())->count('*');
    }

    public function getEventCounters(array $list): array
    {
        $count_of_keys = sizeof(array_keys($list));
        $ev_str = $this->getRecord()->events_counters;
        $counters = [];

        if (!$ev_str) {
            for ($i = 0; $i < sizeof(array_keys($list)); $i++) {
                $counters[] = 0;
            }
        } else {
            $counters = unpack("S" . $count_of_keys, base64_decode($ev_str, true));
        }

        return [
            'counters' => array_combine(array_keys($list), $counters),
            'refresh_time' => $this->getRecord()->events_refresh_time,
        ];
    }

    public function stateEvents(array $state_list): void
    {
        $pack_str = "";

        foreach ($state_list as $item => $id) {
            $pack_str .= "S";
        }

        $this->stateChanges("events_counters", base64_encode(pack($pack_str, ...array_values($state_list))));

        if (!$this->getRecord()->events_refresh_time) {
            $this->stateChanges("events_refresh_time", time());
        }
    }

    public function resetEvents(array $list): void
    {
        $values = [];

        foreach ($list as $key => $val) {
            $values[$key] = 0;
        }

        $this->stateEvents($values);
        $this->stateChanges("events_refresh_time", time());
        $this->save();
    }
}
