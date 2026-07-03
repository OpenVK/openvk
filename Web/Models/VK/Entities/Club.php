<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\Photo as VKPhoto;
use openvk\Web\Models\VK\Entities\User as VKUser;

/**
 * VK-группа/паблик — имитация openvk\Web\Models\Entities\Club.
 * Данные из VK API (groups.getById).
 */
class Club
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function load(int|string $groupId, array $fields = []): ?self
    {
        $fields = array_merge($fields, [
            "photo_50",
            "photo_100",
            "photo_200",
            "photo_max_orig",
            "members_count",
            "description",
            "status",
            "site",
            "activity",
            "counters",
            "verified",
            "cover",
            "screen_name",
            "is_closed",
            "type",
            "can_post",
            "can_see_all_posts",
            "can_upload_doc",
            "can_upload_video",
            "age_limits",
            "market",
            "city",
            "country",
            "links",
            "contacts",
        ]);

        $response = VKAPIClient::i()->groupsGetById($groupId, $fields);

        return !empty($response) ? new self($response[0]) : null;
    }

    /**
     * @return self[]
     */
    public static function getByUser(
        int $userId,
        array $fields = [],
        int $count = 50,
        int $offset = 0,
    ): array {
        $fields = array_merge($fields, [
            "photo_50",
            "photo_100",
            "photo_200",
            "members_count",
            "description",
            "status",
            "screen_name",
            "type",
            "is_closed",
        ]);

        $response = VKAPIClient::i()->groupsGet(
            $userId,
            $fields,
            $count,
            $offset,
        );

        $groups = [];
        foreach ($response["items"] ?? [] as $item) {
            $groups[] = new self($item);
        }

        return $groups;
    }

    /* ===== ID ===== */

    public function getId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getOwnerId(): int
    {
        return -(int) ($this->data["id"] ?? 0);
    }

    public function getRealId(): int
    {
        return $this->getId();
    }

    /* ===== Имя ===== */

    public function getName(): string
    {
        return $this->data["name"] ?? "";
    }

    public function getCanonicalName(): string
    {
        return $this->getName();
    }

    public function getFullName(): string
    {
        return $this->getName();
    }

    /* ===== URL/Shortcode ===== */

    public function getShortCode(): ?string
    {
        return $this->data["screen_name"] ?? null;
    }

    public function getURL(bool $trimBackslash = false): string
    {
        $backslash = $trimBackslash ? "" : "/";
        $sc = $this->getShortCode();

        return $backslash . ($sc ?? "club" . $this->getId());
    }

    /* ===== Аватар ===== */

    public function getAvatarUrl(string $size = "miniscule"): string
    {
        return match ($size) {
            "miniscule" => $this->data["photo_50"] ?? "",
            "small" => $this->data["photo_100"] ?? "",
            "normal" => $this->data["photo_200"] ?? "",
            "large" => $this->data["photo_max_orig"] ??
                ($this->data["photo_200"] ?? ""),
            default => $this->data["photo_200"] ?? "",
        };
    }

    public function getAvatarLink(): string
    {
        return "javascript:void(0)";
    }

    public function getAvatarPhoto(): ?VKPhoto
    {
        return null;
    }

    /* ===== Тип и статус ===== */

    public function getType(): int
    {
        return match ($this->data["type"] ?? "group") {
            "event" => 2,
            default => 1,
        };
    }

    /**
     * Возвращает тип сообщества как строку (VK API стиль).
     */
    public function getVkType(): string
    {
        return $this->data["type"] ?? "group";
    }

    /**
     * Статус закрытости: 0 — открытое, 1 — закрытое, 2 — частное.
     */
    public function getClosed(): int
    {
        return (int) ($this->data["is_closed"] ?? 0);
    }

    public function isClosed(): bool
    {
        return $this->getClosed() !== 0;
    }

    public function isVerified(): bool
    {
        return (bool) ($this->data["verified"] ?? false);
    }

    public function canBeViewedBy($user = null)
    {
        return !$this->isClosed();
    }

    /**
     * Загружает владельца записи (User или Club) по owner_id.
     *
     * @return User|Club|null
     */
    public function getOwner(): User|Club|null
    {
        $ownerId = $this->getOwnerId();

        if ($ownerId > 0) {
            return User::load($ownerId);
        } elseif ($ownerId < 0) {
            return Club::load(abs($ownerId));
        }

        return null;
    }

    /* ===== Описание ===== */

    public function getDescription(): string
    {
        return $this->data["description"] ?? "";
    }

    public function getStatus(): string
    {
        return $this->data["status"] ?? "";
    }

    public function getActivity(): string
    {
        return $this->data["activity"] ?? "";
    }

    public function getSite(): string
    {
        return $this->data["site"] ?? "";
    }

    /* ===== Участники ===== */

    public function getMembersCount(): int
    {
        return (int) ($this->data["members_count"] ?? 0);
    }

    /* ===== Обложка ===== */

    public function getCover(): ?array
    {
        return $this->data["cover"] ?? null;
    }

    public function getCoverUrl(): ?string
    {
        $cover = $this->getCover();
        if (!$cover) {
            return null;
        }

        $images = $cover["images"] ?? [];
        $last = end($images);

        return $last["url"] ?? null;
    }

    /* ===== Счётчики ===== */

    public function getCounters(): ?array
    {
        return $this->data["counters"] ?? null;
    }

    /* ===== Фото ===== */

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

    /* ===== Права ===== */

    public function canPost(): bool
    {
        return (bool) ($this->data["can_post"] ?? true);
    }

    public function canSeeAllPosts(): bool
    {
        return (bool) ($this->data["can_see_all_posts"] ?? true);
    }

    /* ===== VK API Методы ===== */

    public function getFollowers(int $page = 1, int $perPage = 10): array
    {
        $count = $perPage ?? 1000;
        $offset = ($page - 1) * $count;

        $response = VKAPIClient::i()->call("groups.getMembers", [
            "group_id" => $this->getId(),
            "count" => $count,
            "offset" => $offset,
            "fields" => "photo_50,photo_100,photo_200,screen_name,online",
        ]);

        $users = [];
        foreach ($response["items"] ?? [] as $item) {
            $users[] = new VKUser($item);
        }

        return $users;
    }

    public function getFollowersCount(): int
    {
        return (int) $this->getMembersCount();
        $response = VKAPIClient::i()->call("groups.getMembers", [
            "group_id" => $this->getId(),
            "count" => 0,
        ]);

        return (int) ($response["count"] ?? 0);
    }

    public function getDescriptionHtml(): string
    {
        return nl2br(htmlspecialchars($this->getDescription()));
    }

    public function getWebsite(): ?string
    {
        return $this->data["site"] ?? null;
    }

    public function getSubscriptionStatus($who): int
    {
        return 0;
    }

    public function getAlert(): ?string
    {
        return null;
    }

    public function isEveryoneCanCreateTopics(): bool
    {
        return true;
    }

    public function isDisplayTopicsAboveWallEnabled(): bool
    {
        return true;
    }

    public function isBanned(): bool
    {
        return false;
    }

    public function isHideFromGlobalFeedEnabled(): bool
    {
        return false;
    }

    public function getAdministratorsListDisplay(): int
    {
        return -1;
    }

    public function getBackDropPictureURLs(): array
    {
        return [];
    }

    /* ===== Заглушки ===== */

    public function isDeleted(): bool
    {
        return false;
    }
    public function getWallType(): int
    {
        return 1;
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
    public function getManager()
    {
        return null;
    }
    public function getAdministrator()
    {
        return null;
    }

    public function isIgnoredBy($user): bool
    {
        return false;
    }

    public function canBeModifiedBy($who): bool
    {
        return false;
    }

    /**
     * Магический доступ.
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
