<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Themes\{Themepack, Themepacks};
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{Photo, Message, Correspondence, Gift};
use openvk\Web\Models\Repositories\{Users, Clubs, Albums, Gifts, Notifications};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class User extends RowModel
{
    protected $tableName = "profiles";
    
    const TYPE_DEFAULT = 0;
    const TYPE_BOT     = 1;
    
    const SUBSCRIPTION_ABSENT   = 0;
    const SUBSCRIPTION_INCOMING = 1;
    const SUBSCRIPTION_OUTGOING = 2;
    const SUBSCRIPTION_MUTUAL   = 3;
    
    const PRIVACY_NO_ONE          = 0;
    const PRIVACY_ONLY_FRIENDS    = 1;
    const PRIVACY_ONLY_REGISTERED = 2;
    const PRIVACY_EVERYONE        = 3;
    
    const NSFW_INTOLERANT    = 0;
    const NSFW_TOLERANT      = 1;
    const NSFW_FULL_TOLERANT = 2;
    
    protected function _abstractRelationGenerator(string $filename, int $page = 1): \Traversable
    {
        $id     = $this->getId();
        $query  = "SELECT id FROM\n" . file_get_contents(__DIR__ . "/../sql/$filename.tsql");
        $query .= "\n LIMIT 6 OFFSET " . ( ($page - 1) * 6 );
        
        $rels = DatabaseConnection::i()->getConnection()->query($query, $id, $id);
        foreach($rels as $rel) {
            $rel = (new Users)->get($rel->id);
            if(!$rel) continue;
            
            yield $rel;
        }
    }
    
    protected function _abstractRelationCount(string $filename): int
    {
        $id    = $this->getId();
        $query = "SELECT COUNT(*) AS cnt FROM\n" . file_get_contents(__DIR__ . "/../sql/$filename.tsql");
        
        return (int) DatabaseConnection::i()->getConnection()->query($query, $id, $id)->fetch()->cnt;
    }
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }
    
    function getStyle(): string
    {
        return $this->getRecord()->style;
    }
    
    function getTheme(): ?Themepack
    {
        return Themepacks::i()[$this->getStyle()] ?? NULL;
    }
    
    function getStyleAvatar(): int
    {
        return $this->getRecord()->style_avatar;
    }
    
    function hasMilkshakeEnabled(): bool
    {
        return (bool) $this->getRecord()->milkshake;
    }

    function hasMicroblogEnabled(): bool
    {
        return (bool) $this->getRecord()->microblog;
    }
    
    function getChandlerGUID(): string
    {
        return $this->getRecord()->user;
    }
    
    function getChandlerUser(): ChandlerUser
    {
        return new ChandlerUser($this->getRecord()->ref("ChandlerUsers", "user"));
    }
    
    function getURL(): string
    {
        if(!is_null($this->getShortCode()))
            return "/" . $this->getShortCode();
        else
            return "/id" . $this->getId();
    }

    function getFullURL(bool $numberic = false): string
    {
        if(!$numberic && !is_null($this->getShortCode()))
            return ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/" . $this->getShortCode();
        else
            return ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/id" . $this->getId();
    }
    
    function getAvatarUrl(bool $nullForDel = false): string
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
        
        if($this->getRecord()->deleted)
            return $nullForDel ? null : "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        else if($this->isBanned())
            return "$serverUrl/assets/packages/static/openvk/img/banned.jpg";
        
        $avPhoto = $this->getAvatarPhoto();
        if(is_null($avPhoto))
            return $nullForDel ? null : "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        else
            return $avPhoto->getURL();
    }
    
    function getAvatarLink(): string
    {
        $avPhoto = $this->getAvatarPhoto();
        if(!$avPhoto) return "javascript:void(0)";
        
        $pid = $avPhoto->getPrettyId();
        $aid = (new Albums)->getUserAvatarAlbum($this)->getId();
        
        return "/photo$pid?from=album$aid";
    }
    
    function getAvatarPhoto(): ?Photo
    {
        $avAlbum  = (new Albums)->getUserAvatarAlbum($this);
        $avCount  = $avAlbum->getPhotosCount();
        $avPhotos = $avAlbum->getPhotos($avCount, 1);
        
        return iterator_to_array($avPhotos)[0] ?? NULL;
    }
    
    function getFirstName(): string
    {
        return $this->getRecord()->deleted ? "DELETED" : mb_convert_case($this->getRecord()->first_name, MB_CASE_TITLE);
    }
    
    function getLastName(): string
    {
        return $this->getRecord()->deleted ? "DELETED" : mb_convert_case($this->getRecord()->last_name, MB_CASE_TITLE);
    }
    
    function getPseudo(): ?string
    {
        return $this->getRecord()->deleted ? "DELETED" : $this->getRecord()->pseudo;
    }
    
    function getFullName(): string
    {
        if($this->getRecord()->deleted)
            return "DELETED";
        
        $pseudo = $this->getPseudo();
        if(!$pseudo)
            $pseudo = " ";
        else
            $pseudo = " ($pseudo) ";
        
        return $this->getFirstName() . $pseudo . $this->getLastName();
    }
    
    function getCanonicalName(): string
    {
	if($this->getRecord()->deleted)
            return "DELETED";
	else
            return $this->getFirstName() . ' ' . $this->getLastName();
    }
    
    function getPhone(): ?string
    {
        return $this->getRecord()->phone;
    }
    
    function getEmail(): ?string
    {
        return $this->getRecord()->email;
    }
    
    function getOnline(): DateTime
    {
        return new DateTime($this->getRecord()->online);
    }
    
    function getDescription(): ?string
    {
        return $this->getRecord()->about;
    }
    
    function getStatus(): ?string
    {
        return $this->getRecord()->status;
    }
    
    function getShortCode(): ?string
    {
        return $this->getRecord()->shortcode;
    }
    
    function getAlert(): ?string
    {
        return $this->getRecord()->alert;
    }
    
    function getBanReason(): ?string
    {
        return $this->getRecord()->block_reason;
    }
    
    function getType(): int
    {
        return $this->getRecord()->type;
    }
    
    function getCoins(): float
    {
        if(!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"])
            return 0.0;
        
        return $this->getRecord()->coins;
    }
    
    function getRating(): int
    {
        return OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"] ? $this->getRecord()->rating : 0;
    }
    
    function getReputation(): int
    {
        return $this->getRecord()->reputation;
    }
    
    function getRegistrationTime(): DateTime
    {
        return new DateTime($this->getRecord()->since->getTimestamp());
    }
    
    function getRegistrationIP(): string
    {
        return $this->getRecord()->registering_ip;
    }
    
    function getHometown(): ?string
    {
        return $this->getRecord()->hometown;
    }
    
    function getPoliticalViews(): int
    {
        return $this->getRecord()->polit_views;
    }
    
    function getMaritalStatus(): int
    {
        return $this->getRecord()->marital_status;
    }
    
    function getContactEmail(): ?string
    {
        return $this->getRecord()->email_contact;
    }
    
    function getTelegram(): ?string
    {
        return $this->getRecord()->telegram;
    }
    
    function getInterests(): ?string
    {
        return $this->getRecord()->interests;
    }
    
    function getFavoriteMusic(): ?string
    {
        return $this->getRecord()->fav_music;
    }
    
    function getFavoriteFilms(): ?string
    {
        return $this->getRecord()->fav_films;
    }
    
    function getFavoriteShows(): ?string
    {
        return $this->getRecord()->fav_shows;
    }
    
    function getFavoriteBooks(): ?string
    {
        return $this->getRecord()->fav_books;
    }
    
    function getFavoriteQuote(): ?string
    {
        return $this->getRecord()->fav_quote;
    }
    
    function getCity(): ?string
    {
        return $this->getRecord()->city;
    }
    
    function getPhysicalAddress(): ?string
    {
        return $this->getRecord()->address;
    }
    
    function getNotificationOffset(): int
    {
        return $this->getRecord()->notification_offset;
    }

    function getBirthday(): ?DateTime
    {
        return new DateTime($this->getRecord()->birthday);
    }

    function getAge(): ?int
    {
        return (int)floor((time() - $this->getBirthday()->timestamp()) / YEAR);
    }
    
    function get2faSecret(): ?string
    {
        return $this->getRecord()["2fa_secret"];
    }

    function is2faEnabled(): bool
    {
        return !is_null($this->get2faSecret());
    }

    function updateNotificationOffset(): void
    {
        $this->stateChanges("notification_offset", time());
    }
    
    function getLeftMenuItemStatus(string $id): bool
    {
        return (bool) bmask($this->getRecord()->left_menu, [
            "length"   => 1,
            "mappings" => [
                "photos",
                "videos",
                "messages",
                "notes",
                "groups",
                "news",
                "links",
            ],
        ])->get($id);
    }
    
    function getPrivacySetting(string $id): int
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
            ],
        ])->get($id);
    }
    
    function getPrivacyPermission(string $permission, ?User $user = NULL): bool
    {
        $permStatus = $this->getPrivacySetting($permission);
        if(!$user)
            return $permStatus === User::PRIVACY_EVERYONE;
        else if($user->getId() === $this->getId())
            return true;
        
        switch($permStatus) {
            case User::PRIVACY_ONLY_FRIENDS:
                return $this->getSubscriptionStatus($user) === User::SUBSCRIPTION_MUTUAL;
            case User::PRIVACY_ONLY_REGISTERED:
            case User::PRIVACY_EVERYONE:
                return true;
            default:
                return false;
        }
    }
    
    function getProfileCompletenessReport(): object
    {
        $incompleteness = 0;
        $unfilled       = [];
        
        if(!$this->getRecord()->status) {
            $unfilled[]      = "status";
            $incompleteness += 15;
        }
        if(!$this->getRecord()->telegram) {
            $unfilled[]      = "telegram";
            $incompleteness += 15;
        }
        if(!$this->getRecord()->email) {
            $unfilled[]      = "email";
            $incompleteness += 20;
        }
        if(!$this->getRecord()->city) {
            $unfilled[]      = "city";
            $incompleteness += 20;
        }
        if(!$this->getRecord()->interests) {
            $unfilled[]      = "interests";
            $incompleteness += 20;
        }
        
        $total = max(100 - $incompleteness + $this->getRating(), 0);
        if(ovkGetQuirk("profile.rating-bar-behaviour") === 0)
	    if ($total >= 100)
            $percent = round(($total / 10**strlen(strval($total))) * 100, 0);
        else
		    $percent = min($total, 100);
        
        return (object) [
            "total"    => $total,
            "percent"  => $percent,
            "unfilled" => $unfilled,
        ];
    }
    
    function getFriends(int $page = 1): \Traversable
    {
        return $this->_abstractRelationGenerator("get-friends", $page);
    }
    
    function getFriendsCount(): int
    {
        return $this->_abstractRelationCount("get-friends");
    }
    
    function getFollowers(int $page = 1): \Traversable
    {
        return $this->_abstractRelationGenerator("get-followers", $page);
    }
    
    function getFollowersCount(): int
    {
        return $this->_abstractRelationCount("get-followers");
    }
    
    function getSubscriptions(int $page = 1): \Traversable
    {
        return $this->_abstractRelationGenerator("get-subscriptions-user", $page);
    }
    
    function getSubscriptionsCount(): int
    {
        return $this->_abstractRelationCount("get-subscriptions-user");
    }

    function getUnreadMessagesCount(): int
    {
        return sizeof(DatabaseConnection::i()->getContext()->table("messages")->where(["recipient_id" => $this->getId(), "unread" => 1]));
    }
    
    function getClubs(int $page = 1, bool $admin = false): \Traversable
    {
        if($admin) {
            $id     = $this->getId();
            $query  = "SELECT `id` FROM `groups` WHERE `owner` = ? UNION SELECT `club` as `id` FROM `group_coadmins` WHERE `user` = ?";
            $query .= " LIMIT " . OPENVK_DEFAULT_PER_PAGE . " OFFSET " . ($page - 1) * OPENVK_DEFAULT_PER_PAGE;

            $sel = DatabaseConnection::i()->getConnection()->query($query, $id, $id);
            foreach($sel as $target) {
                $target = (new Clubs)->get($target->id);
                if(!$target) continue;

                yield $target;
            }
        } else {
            $sel = $this->getRecord()->related("subscriptions.follower")->page($page, OPENVK_DEFAULT_PER_PAGE);
            foreach($sel->where("model", "openvk\\Web\\Models\\Entities\\Club") as $target) {
                $target = (new Clubs)->get($target->target);
                if(!$target) continue;

                yield $target;
            }
        }
    }
    
    function getClubCount(bool $admin = false): int
    {
        if($admin) {
            $id    = $this->getId();
            $query = "SELECT COUNT(*) AS `cnt` FROM (SELECT `id` FROM `groups` WHERE `owner` = ? UNION SELECT `club` as `id` FROM `group_coadmins` WHERE `user` = ?) u0;";

            return (int) DatabaseConnection::i()->getConnection()->query($query, $id, $id)->fetch()->cnt;
        } else {
            $sel = $this->getRecord()->related("subscriptions.follower");
            $sel = $sel->where("model", "openvk\\Web\\Models\\Entities\\Club");

            return sizeof($sel);
        }
    }
    
    function getPinnedClubs(): \Traversable
    {
        foreach($this->getRecord()->related("groups.owner")->where("owner_club_pinned", true) as $target) {
            $target = (new Clubs)->get($target->id);
            if(!$target) continue;

            yield $target;
        }

        foreach($this->getRecord()->related("group_coadmins.user")->where("club_pinned", true) as $target) {
            $target = (new Clubs)->get($target->club);
            if(!$target) continue;

            yield $target;
        }
    }

    function getPinnedClubCount(): int
    {
        return sizeof($this->getRecord()->related("groups.owner")->where("owner_club_pinned", true)) + sizeof($this->getRecord()->related("group_coadmins.user")->where("club_pinned", true));
    }

    function isClubPinned(Club $club): bool
    {
        if($club->getOwner()->getId() === $this->getId())
            return $club->isOwnerClubPinned();

        $manager = $club->getManager($this);
        if(!is_null($manager))
            return $manager->isClubPinned();

        return false;
    }

    function getMeetings(int $page = 1): \Traversable
    {
        $sel = $this->getRecord()->related("event_turnouts.user")->page($page, OPENVK_DEFAULT_PER_PAGE);
        foreach($sel as $target) {
            $target = (new Clubs)->get($target->event);
            if(!$target) continue;
            
            yield $target;
        }
    }
    
    function getMeetingCount(): int
    {
        return sizeof($this->getRecord()->related("event_turnouts.user"));
    }
    
    function getGifts(int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $gifts = $this->getRecord()->related("gift_user_relations.receiver")->order("sent DESC")->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        foreach($gifts as $rel) {
            yield (object) [
                "sender"  => (new Users)->get($rel->sender),
                "gift"    => (new Gifts)->get($rel->gift),
                "caption" => $rel->comment,
                "anon"    => $rel->anonymous,
                "sent"    => new DateTime($rel->sent),
            ];
        }
    }
    
    function getGiftCount(): int
    {
        return sizeof($this->getRecord()->related("gift_user_relations.receiver"));
    }

    function get2faBackupCodes(): \Traversable
    {
        $sel = $this->getRecord()->related("2fa_backup_codes.owner");
        foreach($sel as $target)
            yield $target->code;
    }

    function get2faBackupCodeCount(): int
    {
        return sizeof($this->getRecord()->related("2fa_backup_codes.owner"));
    }

    function generate2faBackupCodes(): void
    {
        $codes = [];

        for($i = 0; $i < 10 - $this->get2faBackupCodeCount(); $i++) {
            $codes[] = [
                owner => $this->getId(),
                code => random_int(10000000, 99999999)
            ];
        }

        if(sizeof($codes) > 0)
            DatabaseConnection::i()->getContext()->table("2fa_backup_codes")->insert($codes);
    }

    function use2faBackupCode(int $code): bool
    {
        return (bool) $this->getRecord()->related("2fa_backup_codes.owner")->where("code", $code)->delete();   
    }
    
    function getSubscriptionStatus(User $user): int
    {
        $subbed = !is_null($this->getRecord()->related("subscriptions.follower")->where([
            "model"  => static::class,
            "target" => $user->getId(),
        ])->fetch());
        $followed = !is_null($this->getRecord()->related("subscriptions.target")->where([
            "model"    => static::class,
            "follower" => $user->getId(),
        ])->fetch());
        
        if($subbed && $followed) return User::SUBSCRIPTION_MUTUAL;
        if($subbed)   return User::SUBSCRIPTION_INCOMING;
        if($followed) return User::SUBSCRIPTION_OUTGOING;
        
        return User::SUBSCRIPTION_ABSENT;
    }
    
    function getNotificationsCount(bool $archived = false): int
    {
        return (new Notifications)->getNotificationCountByUser($this, $this->getNotificationOffset(), $archived);
    }
    
    function getNotifications(int $page, bool $archived = false): \Traversable
    {
        return (new Notifications)->getNotificationsByUser($this, $this->getNotificationOffset(), $archived, $page);
    }
    
    function getPendingPhoneVerification(): ?ActiveRow
    {
        return $this->getRecord()->ref("number_verification", "id");
    }
    
    function getRefLinkId(): string
    {
        $hash = hash_hmac("Snefru", (string) $this->getId(), CHANDLER_ROOT_CONF["security"]["secret"], true);
        
        return dechex($this->getId()) . " " . base64_encode($hash);
    }
    
    function getNsfwTolerance(): int
    {
        return $this->getRecord()->nsfw_tolerance;
    }
    
    function isFemale(): bool
    {
        return (bool) $this->getRecord()->sex;
    }
    
    function isVerified(): bool
    {
        return (bool) $this->getRecord()->verified;
    }
    
    function isBanned(): bool
    {
        return !is_null($this->getBanReason());
    }
    
    function isOnline(): bool
    {
        return time() - $this->getRecord()->online <= 300;
    }
    
    function prefersNotToSeeRating(): bool
    {
        return !((bool) $this->getRecord()->show_rating);
    }
    
    function hasPendingNumberChange(): bool
    {
        return !is_null($this->getPendingPhoneVerification());
    }
    
    function gift(User $sender, Gift $gift, ?string $comment = NULL, bool $anonymous = false): void
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
    
    function ban(string $reason, bool $deleteSubscriptions = true): void
    {
        if($deleteSubscriptions) {
            $subs = DatabaseConnection::i()->getContext()->table("subscriptions");
            $subs = $subs->where(
                "follower = ? OR (target = ? AND model = ?)",
                $this->getId(),
                $this->getId(),
                get_class($this),
            );
            $subs->delete();
        }
        
        $this->setBlock_Reason($reason);
        $this->save();
    }
    
    function verifyNumber(string $code): bool
    {
        $ver = $this->getPendingPhoneVerification();
        if(!$ver) return false;
        
        try {
            if(sodium_memcmp((string) $ver->code, $code) === -1) return false;
        } catch(\SodiumException $ex) {
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
    
    function setNsfwTolerance(int $tolerance): void
    {
        $this->stateChanges("nsfw_tolerance", $tolerance);
    }
    
    function setPrivacySetting(string $id, int $status): void
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
            ],
        ])->set($id, $status)->toInteger());
    }
    
    function setLeftMenuItemStatus(string $id, bool $status): void
    {
        $mask = bmask($this->changes["left_menu"] ?? $this->getRecord()->left_menu, [
            "length"   => 1,
            "mappings" => [
                "photos",
                "videos",
                "messages",
                "notes",
                "groups",
                "news",
                "links",
            ],
        ])->set($id, (int) $status)->toInteger();
        
        $this->stateChanges("left_menu", $mask);
    }
    
    function setShortCode(?string $code = NULL, bool $force = false): ?bool
    {
        if(!is_null($code)) {
            if(strlen($code) < OPENVK_ROOT_CONF["openvk"]["preferences"]["shortcodes"]["minLength"] && !$force)
                return false;
            if(!preg_match("%^[a-z][a-z0-9\\.\\_]{0,30}[a-z0-9]$%", $code))
                return false;
            if(in_array($code, OPENVK_ROOT_CONF["openvk"]["preferences"]["shortcodes"]["forbiddenNames"]))
                return false;
            if(\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$code")[0]->presenter !== "UnknownTextRouteStrategy")
                return false;
	    
            $pClub = DatabaseConnection::i()->getContext()->table("groups")->where("shortcode", $code)->fetch();
                if(!is_null($pClub))
                    return false;
        }
        
        $this->stateChanges("shortcode", $code);
        return true;
    }
    
    function setPhoneWithVerification(string $phone): string
    {
        $code = unpack("S", openssl_random_pseudo_bytes(2))[1];
        
        if($this->hasPendingNumberChange()) {
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
    function setOnline(int $time): bool
    {
        $this->stateChanges("shortcode", $this->getRecord()->shortcode); #fix KABOBSQL
        $this->stateChanges("online", $time);
        
        return true;
    }
    
    function adminNotify(string $message): bool
    {
        $admId = OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["adminAccount"];
        if(!$admId)
            return false;
        else if(is_null($admin = (new Users)->get($admId)))
            return false;
        
        $cor = new Correspondence($admin, $this);
        $msg = new Message;
        $msg->setContent($message);
        $cor->sendMessage($msg, true);
        
        return true;
    }

    function isDeleted(): bool
    {
	if ($this->getRecord()->deleted == 1)
	return TRUE;
	else
        return FALSE;
    }

    /**
     * 0 - Default status
     * 1 - Incognito online status
     * 2 - Page of a dead person
     */
    function onlineStatus(): int
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

	function getWebsite(): ?string
	{
		return $this->getRecord()->website;
	}
    
    use Traits\TSubscribable;
}
