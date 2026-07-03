<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Club as VKClub;
use openvk\Web\Models\VK\Entities\Photo as VKPhoto;

/**
 * VK-пользователь — обёртка ответа users.get из VK API.
 * Имитация openvk\Web\Models\Entities\User.
 */
class User
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Загружает пользователя из VK API.
     */
    public static function load(int|string $userId, array $fields = []): ?self
    {
        $fields = array_merge($fields, [
            "photo_50",
            "photo_100",
            "photo_200",
            "photo_max_orig",
            "sex",
            "bdate",
            "city",
            "country",
            "status",
            "screen_name",
            "online",
            "online_mobile",
            "verified",
            "followers_count",
            "about",
            "activities",
            "interests",
            "music",
            "movies",
            "tv",
            "books",
            "games",
            "universities",
            "schools",
            "home_town",
            "personal",
            "relation",
            "counters",
            "connections",
            "site",
            "can_post",
            "can_see_all_posts",
            "can_write_private_message",
            "can_send_friend_request",
            "timezone",
            "last_seen",
        ]);

        $response = VKAPIClient::i()->usersGet($userId, $fields);

        return !empty($response) ? new self($response[0]) : null;
    }

    /* ===== ID и идентификация ===== */

    public function getId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getChandlerGUID(): string
    {
        return "vk_" . $this->getId();
    }

    /**
     * Заглушка — возвращает объект-пустышку, у которого can()/model()
     * возвращают сами себя, а whichBelongsTo() всегда true.
     */
    public function getChandlerUser(): object
    {
        return new class {
            public function can(string $action): self
            {
                return $this;
            }

            public function model(string $model): self
            {
                return $this;
            }

            public function getId(): int
            {
                return 10;
            }

            public function whichBelongsTo(?int $to): bool
            {
                return false;
            }
        };
    }

    /* ===== Имя ===== */

    public function getFirstName(): string
    {
        return $this->data["first_name"] ?? "";
    }

    public function getLastName(): string
    {
        return $this->data["last_name"] ?? "";
    }

    public function getPseudo(): ?string
    {
        return null; // VK не имеет псевдонимов
    }

    public function getFullName(): string
    {
        return trim($this->getFirstName() . " " . $this->getLastName());
    }

    public function getCanonicalName(): string
    {
        return $this->getFullName();
    }

    /* ===== URL/Shortcode ===== */

    public function getShortCode(): ?string
    {
        return $this->data["screen_name"] ?? null;
    }

    /**
    public function getURL(bool $trimBackslash = false): string
    {
        $backslash = $trimBackslash ? "" : "/";
        $sc = $this->getShortCode();

        return $backslash . ($sc ?? "id" . $this->getId());
    }

    /* ===== Аватар ===== */

    public function getAvatarUrl(
        string $size = "miniscule",
        $avPhoto = null,
    ): string {
        return match ($size) {
            "miniscule" => $this->data["photo_50"] ?? "",
            "small" => $this->data["photo_100"] ?? "",
            "normal" => $this->data["photo_200"] ?? "",
            "large" => $this->data["photo_max_orig"] ??
                ($this->data["photo_200"] ?? ""),
            default => $this->data["photo_200"] ?? "",
        };
    }

    public function getURL(bool $trimBackslash = false): string
    {
        return $this->getShortCode() ?? "id" . $this->getId();
    }

    public function isBroadcastEnabled(): bool
    {
        return (bool) ($this->data["broadcast"] ?? false);
    }

    public function isNeutral(): bool
    {
        return false;
    }

    public function getPhoto50(): string
    {
        return $this->data["photo_50"] ?? "";
    }
    public function getPhoto100(): string
    {
        return $this->data["photo_100"] ?? "";
    }
    public function getPhoto200(): string
    {
        return $this->data["photo_200"] ?? "";
    }
    public function getPhotoMaxOrig(): string
    {
        return $this->data["photo_max_orig"] ?? $this->getPhoto200();
    }

    /**
     * Заглушка — в VK API нет альбома аватара.
     */
    public function getAvatarPhoto(): ?VKPhoto
    {
        return null;
    }
    public function getAvatarAlbum()
    {
        return null;
    }
    public function getAvatarLink(): string
    {
        return "javascript:void(0)";
    }

    /* ===== Онлайн ===== */

    public function isOnline(): bool
    {
        return (bool) ($this->data["online"] ?? false);
    }

    public function getOnline(): \openvk\Web\Util\DateTime
    {
        $ts = $this->data["last_seen"]["time"] ?? time();

        return new \openvk\Web\Util\DateTime($ts);
    }

    /* ===== Статус и описание ===== */

    public function getStatus(): string
    {
        return $this->data["status"] ?? "";
    }

    public function getAbout(): string
    {
        return $this->data["about"] ?? "";
    }

    public function getDescription(): ?string
    {
        return $this->getAbout();
    }

    public function getInterests(): ?string
    {
        return $this->data["interests"] ?? null;
    }

    public function getActivities(): ?string
    {
        return $this->data["activities"] ?? null;
    }

    public function getFavoriteMusic(): ?string
    {
        return $this->data["music"] ?? null;
    }

    public function getFavoriteFilms(): ?string
    {
        return $this->data["movies"] ?? null;
    }

    public function getFavoriteShows(): ?string
    {
        return $this->data["tv"] ?? null;
    }

    public function getFavoriteBooks(): ?string
    {
        return $this->data["books"] ?? null;
    }

    public function getFavoriteGames(): ?string
    {
        return $this->data["games"] ?? null;
    }

    /* ===== Пол, дата рождения, город ===== */

    public function getSex(): int
    {
        return (int) ($this->data["sex"] ?? 0);
    }

    public function isFemale(): bool
    {
        return $this->getSex() === 1;
    }

    public function isMale(): bool
    {
        return $this->getSex() === 2;
    }

    public function getBDate(): ?string
    {
        return $this->data["bdate"] ?? null;
    }

    public function getCity(): ?string
    {
        return $this->data["city"] ? $this->data["city"]["title"] : null;
    }

    public function getCountry(): ?array
    {
        return $this->data["country"] ?? null;
    }

    public function getHometown(): ?string
    {
        return $this->data["home_town"] ?? null;
    }

    /* ===== Контакты ===== */

    public function getPhone(): ?string
    {
        return null; // VK API не отдаёт телефон
    }

    public function getEmail(): ?string
    {
        return null;
    }

    public function getSite(): ?string
    {
        return $this->data["site"] ?? null;
    }

    /* ===== Семейное положение ===== */

    public function getMaritalStatus(): int
    {
        return (int) ($this->data["relation"] ?? 0);
    }

    public function getMaritalStatusUser(): ?self
    {
        return null;
    }

    public function getLocalizedMaritalStatus(?bool $prefix = false): string
    {
        return "";
    }

    public function getMaritalStatusUserPrefix(): ?string
    {
        return null;
    }

    public function getRealId(): int
    {
        return $this->getId();
    }

    /**
     * Возвращает статус подписки (0 — нет, 1 — входящая, 2 — исходящая, 3 — взаимная).
     */
    public function getSubscriptionStatus($who): int
    {
        return 0;
    }

    public function getMorphedName(
        string $case = "genitive",
        bool $fullName = true,
        bool $startWithLastName = true,
    ): string {
        return $this->getFullName();
    }

    /* ===== Политические взгляды, etc ===== */

    public function getPoliticalViews(): int
    {
        return 0;
    }

    /* ===== Счётчики ===== */

    public function getFollowersCount(): int
    {
        return (int) ($this->data["followers_count"] ?? 0);
    }

    public function getCounters(): ?array
    {
        return $this->data["counters"] ?? null;
    }

    /* ===== Разное ===== */

    public function getStyle(): string
    {
        return "auto"; // VK не имеет кастомных стилей
    }

    public function getStyleAvatar(): int
    {
        return 0;
    }
    public function hasMilkshakeEnabled(): bool
    {
        return false;
    }
    public function hasMicroblogEnabled(): bool
    {
        return true;
    }
    public function getMainPage(): int
    {
        return 0;
    }

    public function getType(): int
    {
        return 0;
    }
    public function getCoins(): float
    {
        return 0.0;
    }
    public function getRating(): int
    {
        return 0;
    }
    public function getReputation(): int
    {
        return 0;
    }

    public function getRegistrationTime(): \openvk\Web\Util\DateTime
    {
        return new \openvk\Web\Util\DateTime(0);
    }
    public function getRegistrationIP(): string
    {
        return "";
    }

    public function getAlert(): ?string
    {
        return null;
    }
    public function getTelegram(): ?string
    {
        return null;
    }
    public function getContactEmail(): ?string
    {
        return null;
    }

    public function isVerified(): bool
    {
        return (bool) ($this->data["verified"] ?? false);
    }

    public function isDeleted(): bool
    {
        return ($this->data["deactivated"] ?? "") === "deleted";
    }

    public function isBanned(): bool
    {
        return ($this->data["deactivated"] ?? "") === "banned";
    }

    public function isDeactivated(): bool
    {
        return !empty($this->data["deactivated"]);
    }

    public function isActivated(): bool
    {
        return true;
    }

    public function onlineStatus(): int
    {
        return 1;
    }

    public function getFriendsBday(bool $today): array
    {
        return [];
    }

    public function getBirthday(): ?\openvk\Web\Util\DateTime
    {
        $bdate = $this->data["bdate"] ?? null;
        if (!$bdate) {
            return null;
        }

        $parts = explode(".", $bdate);
        if (count($parts) >= 3) {
            $ts = strtotime($parts[2] . "-" . $parts[1] . "-" . $parts[0]);
            return $ts ? new \openvk\Web\Util\DateTime($ts) : null;
        }

        return null;
    }

    public function getBirthdayPrivacy(): int
    {
        $bdate = $this->data["bdate"] ?? null;
        if ($bdate && substr_count($bdate, ".") >= 2) {
            return 0;
        }

        return 1;
    }

    public function getAge(): int
    {
        $birthday = $this->getBirthday();
        if (!$birthday) {
            return 0;
        }

        return (int) ((time() - $birthday->timestamp()) / 31536000);
    }

    public function getWebsite(): ?string
    {
        return $this->data["site"] ?? null;
    }

    public function getPhysicalAddress(): ?string
    {
        return null;
    }

    public function getFavoriteQuote(): ?string
    {
        return $this->data["quotes"] ?? null;
    }

    public function getGiftCount(): int
    {
        return 0;
    }

    public function isBannedInSupport(): bool
    {
        return false;
    }

    public function isHideFromGlobalFeedEnabled(): bool
    {
        return false;
    }

    public function hasPendingNumberChange(): bool
    {
        return false;
    }

    public function getPronouns(): int
    {
        return $this->getSex();
    }

    public function is2faEnabled(): bool
    {
        return false;
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function getProfileType(): int
    {
        return 0;
    }

    public function getUserAlbums(
        $user,
        int $page = 1,
        ?int $perPage = null,
    ): \Traversable {
        return new \ArrayIterator([]);
    }

    public function getBackDropPictureURLs(): array
    {
        return [];
    }

    public function isPrivate(): bool
    {
        return (bool) ($this->data["is_closed"] ?? false);
    }

    public function getProfilePictureURLs(): array
    {
        return [];
    }

    public function getRequestsCount(): int
    {
        return (int) ($this->data["requests_count"] ?? 0);
    }

    public function getLeftMenuItemStatus(string $id): bool
    {
        return true;
    }

    public function getNotificationsCount(bool $archive = false): int
    {
        try {
            $response = VKAPIClient::i()->call("account.getCounters", []);

            return (int) ($response["notifications"] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getUnreadMessagesCount(): int
    {
        try {
            $response = VKAPIClient::i()->call("account.getCounters", []);

            return (int) ($response["messages"] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getOnlinePlatform(): ?string
    {
        return null;
    }
    public function getOnlinePlatformDetails(): array
    {
        return [];
    }

    public function prefersNotToSeeRating(): bool
    {
        return true;
    }

    public function getProfileCompletenessReport(): object
    {
        return (object) [];
    }

    public function getFriendsCount(): int
    {
        $response = VKAPIClient::i()->call("friends.get", [
            "user_id" => $this->getId(),
            "count" => 0,
        ]);

        return (int) ($response["count"] ?? 0);
    }

    public function getFriends(
        int $page = 1,
        ?int $perPage = null,
    ): \Traversable {
        $perPage ??= 50;
        $offset = ($page - 1) * $perPage;
        $response = VKAPIClient::i()->call("friends.get", [
            "user_id" => $this->getId(),
            "count" => $perPage,
            "offset" => $offset,
        ]);

        $friends = [];
        foreach ($response["items"] ?? [] as $friendId) {
            $friend = new User($friendId);
            if ($friend !== null) {
                $friends[] = $friend;
            }
        }

        return new \ArrayIterator($friends);
    }

    public function getFriendsOnlineCount(): int
    {
        $response = VKAPIClient::i()->call("friends.getOnline", [
            "user_id" => $this->getId(),
        ]);

        return (int) ($response["count"] ?? 0);
    }

    public function getFriendsOnline(
        int $page = 1,
        ?int $perPage = null,
    ): \Traversable {
        $perPage ??= 50;
        $offset = ($page - 1) * $perPage;
        $response = VKAPIClient::i()->call("friends.getOnline", [
            "user_id" => $this->getId(),
            "count" => $perPage,
            "offset" => $offset,
        ]);

        $friends = [];
        foreach ($response["items"] ?? [] as $friendId) {
            $friend = User::load($friendId);
            if ($friend !== null) {
                $friends[] = $friend;
            }
        }

        return new \ArrayIterator($friends);
    }

    public function getClubCount(): int
    {
        $response = VKAPIClient::i()->call("groups.get", [
            "user_id" => $this->getId(),
            "count" => 0,
        ]);

        return (int) ($response["count"] ?? 0);
    }

    public function getClubs(int $page = 1, bool $admin = false, int $perPage = 10): \Traversable
    {
        $perPage ??= 50;
        $offset = ($page - 1) * $perPage;
        bdump($perPage);
        $response = VKAPIClient::i()->call("groups.get", [
            "user_id" => $this->getId(),
            "extended" => 1,
            "count" => $perPage,
            "offset" => $offset,
        ]);

        $clubs = [];
        foreach ($response["items"] ?? [] as $clubData) {
            $clubs[] = new VKClub($clubData);
        }

        return new \ArrayIterator($clubs);
    }

    public function getMeetingCount(): int
    {
        return 0;
    }

    public function canBeModifiedBy($who): bool
    {
        return false;
    }

    public function getMeetings(
        int $page = 1,
        ?int $perPage = null,
    ): \Traversable {
        return new \ArrayIterator([]);
    }

    public function getPinnedClubs(): array
    {
        return [];
    }

    public function getNsfwTolerance(): int
    {
        return (int) ($this->data["nsfw_tolerance"] ?? 0);
    }

    public function canPost(): bool
    {
        return (bool) ($this->data["can_post"] ?? true);
    }

    public function canWritePrivateMessage(): bool
    {
        return (bool) ($this->data["can_write_private_message"] ?? true);
    }

    /**
     * Заглушки для методов соцграфа — VK API не даёт доступа
     * к настройкам приватности, чёрным спискам и т.д.
     * Возвращаем permissive значения (можно смотреть, не игнор, не в ЧС).
     */

    public function canBeViewedBy($who = null): bool
    {
        return !$this->isDeleted() && !$this->isBanned();
    }

    public function getPrivacyPermission(string $permission, $who = null): bool
    {
        if ($this->isDeleted() || $this->isBanned()) {
            return false;
        }

        // VK публичные профили всегда доступны для чтения базовой информации
        return true;
    }

    public function getPrivacySetting(string $id): int
    {
        return 3;
    }

    public function isBlacklistedBy($user): bool
    {
        return false;
    }

    public function isIgnoredBy($user): bool
    {
        return false;
    }

    public function isClubPinned($club): bool
    {
        return false;
    }

    public function getPinnedClubCount(): int
    {
        return 0;
    }

    /**
     * Аудио-статус — заглушка.
     */
    public function getCurrentAudioStatus(): ?array
    {
        return null;
    }

    public function getNewBanTime()
    {
        return 0;
    }

    /**
     * Дополнительные поля профиля — VK API отдаёт
     * interests, music, movies, tv, books, games, about.
     */
    public function getAdditionalFields(bool $raw = false): array
    {
        $fields = [
            "interests" => $this->data["interests"] ?? "",
            "fav_music" => $this->data["music"] ?? "",
            "fav_films" => $this->data["movies"] ?? "",
            "fav_shows" => $this->data["tv"] ?? "",
            "fav_books" => $this->data["books"] ?? "",
            "fav_games" => $this->data["games"] ?? "",
            "about" => $this->data["about"] ?? "",
            "activities" => $this->data["activities"] ?? "",
        ];

        if ($raw) {
            return $fields;
        }

        // Отфильтровываем пустые
        return array_filter($fields, fn($v) => !empty($v));
    }

    /* ===== Mutators-заглушки (VK mode read-only) ===== */

    public function getBroadcastList(string $filter = "all", bool $extended = false): array
    {
        return [];
    }

    public function setOnline(int $time): void {}

    public function setClient_name(?string $name): void {}

    public function setFirst_Name(string $name): void {}
    public function setLast_Name(string $name): void {}
    public function setPseudo(?string $pseudo): void {}
    public function setStatus(?string $status): void {}
    public function setHometown(?string $hometown): void {}
    public function setBirthday(?int $birthday): void {}
    public function setBirthday_Privacy(int $privacy): void {}
    public function save(?bool $log = false): void {}

    public function getNotifications(int $page = 1, bool $archive = false): \Traversable
    {
        $perPage = OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        try {
            $response = VKAPIClient::i()->call("notifications.get", [
                "count"    => $perPage,
                "offset"   => $offset,
                "extended" => 1,
            ]);
        } catch (\Throwable) {
            return new \ArrayIterator([]);
        }

        $profiles = $response["profiles"] ?? [];
        $groups   = $response["groups"] ?? [];

        $notifications = [];
        foreach ($response["items"] ?? [] as $item) {
            $item["_profiles"] = $profiles;
            $item["_groups"]   = $groups;
            $notifications[] = new \openvk\Web\Models\VK\Entities\Notification($item);
        }

        return new \ArrayIterator($notifications);
    }

    public function updateNotificationOffset()
    {
        return null;
    }

    /**
     * Магический доступ к полям.
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
