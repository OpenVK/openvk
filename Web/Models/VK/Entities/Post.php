<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

use openvk\VKAPIClient\VKAPIClient;
use openvk\VKAPIClient\VKAPIException;
use openvk\Web\Models\VK\Entities\Audio;
use openvk\Web\Models\VK\Entities\Document;
use openvk\Web\Models\VK\Entities\Photo;
use openvk\Web\Models\VK\Entities\Video;
use openvk\Web\Util\Makima\Makima;

/**
 * VK-пост/запись — обёртка ответа wall.get / newsfeed.get из VK API.
 * Не наследует DBEntity, не использует БД.
 */
class Post extends VkEntity
{
    /**
     * Загружает посты со стены.
     *
     * @param int|string $ownerId ID владельца (для группы — отрицательное число)
     * @return self[]
     */
    public static function getWall(
        int|string $ownerId,
        int $count = 20,
        int $offset = 0,
        array $extra = [],
    ): array {
        $response = VKAPIClient::i()->wallGet(
            $ownerId,
            $count,
            $offset,
            $extra,
        );

        $posts = [];
        foreach ($response["items"] ?? [] as $item) {
            $posts[] = new self($item);
        }

        return $posts;
    }

    /**
     * Загружает новости из ленты.
     *
     * @return array{items: self[], profiles: User[], groups: Club[]}
     */
    public static function getNewsfeed(
        array $filters = ["post"],
        int $count = 30,
        array $extra = [],
    ): array {
        $response = VKAPIClient::i()->newsfeedGet($filters, $count, $extra);

        $profiles = [];
        foreach ($response["profiles"] ?? [] as $profileData) {
            $profiles[] = new User($profileData);
        }

        $groups = [];
        foreach ($response["groups"] ?? [] as $groupData) {
            $groups[] = new Club($groupData);
        }

        $items = [];
        foreach ($response["items"] ?? [] as $itemData) {
            $items[] = new self($itemData);
        }

        return [
            "items" => $items,
            "profiles" => $profiles,
            "groups" => $groups,
        ];
    }

    /* ===== Основные геттеры ===== */

    public function getFromId(): int
    {
        return (int) ($this->data["from_id"] ?? 0);
    }

    public function getDate(): ?int
    {
        return $this->getTime();
    }

    public function getText(): string
    {
        return $this->data["text"] ?? "";
    }

    /**
     * Возвращает репосты в VK-формате.
     */
    public function getReposts(): ?array
    {
        return $this->data["reposts"] ?? null;
    }

    public function getRepostsCount(): int
    {
        return (int) ($this->getReposts()["count"] ?? 0);
    }

    /**
     * Возвращает просмотры в VK-формате.
     */
    public function getViews(): ?array
    {
        return $this->data["views"] ?? null;
    }

    public function getViewsCount(): int
    {
        return (int) ($this->getViews()["count"] ?? 0);
    }

    /**
     * Возвращает комментарии в VK-формате.
     */
    public function getCommentsArr(): ?array
    {
        return $this->data["comments"] ?? null;
    }

    public function getCommentsCount(): int
    {
        return (int) ($this->getCommentsArr()["count"] ?? 0);
    }

    public function canComment(): bool
    {
        return !empty($this->getCommentsArr()["can_post"]);
    }

    public function getChildrenWithLayout(int $w, int $h = -1): object
    {
        if ($h < 0) {
            $h = $w;
        }

        $children = iterator_to_array($this->getChildren());
        $skipped  = $photos = $result = [];
        foreach ($children as $child) {
            if ($child instanceof Photo || $child instanceof Video && $child->getDimensions()) {
                $photos[] = $child;
                continue;
            }

            $skipped[] = $child;
        }

        $height = "unset";
        $width  = $w;
        if (sizeof($photos) < 2) {
            if (isset($photos[0])) {
                $result[] = ["100%", "unset", $photos[0], "unset"];
            }
        } else {
            $mak    = new Makima($photos);
            $layout = $mak->computeMasonryLayout($w, $h);
            $height = $layout->height;
            $width  = $layout->width;
            for ($i = 0; $i < sizeof($photos); $i++) {
                $tile = $layout->tiles[$i];
                $result[] = [$tile->width . "px", $tile->height . "px", $photos[$i], "left"];
            }
        }

        return (object) [
            "width"  => $width . "px",
            "height" => $height . "px",
            "tiles"  => $result,
            "extras" => $skipped,
        ];
    }

    /**
     * Возвращает вложения.
     *
     * @return object[]
     */
    public function getChildren(): array
    {
        $result = [];
        foreach ($this->data["attachments"] ?? [] as $att) {
            $type = $att["type"] ?? "unknown";
            $obj = null;
            switch ($type) {
                case "photo": $obj = new Photo($att["photo"] ?? []); break;
                case "video": $obj = new Video($att["video"] ?? []); break;
                case "audio": $obj = new Audio($att["audio"] ?? []); break;
                case "doc": $obj = new Document($att["doc"] ?? []); break;
                case "link": $obj = (object) ($att["link"] ?? []); break;
                default: $obj = (object) $att; break;
            }
            if ($obj) $result[] = $obj;
        }
        return $result;
    }

    /**
     * Возвращает вложения, сгруппированные по типу.
     *
     * @return array<string, object[]>
     */
    public function getAttachmentsByType(): array
    {
        $grouped = [];
        foreach ($this->getAttachments() as $attachment) {
            $type = "unknown";
            if ($attachment instanceof Photo) $type = "photo";
            elseif ($attachment instanceof Video) $type = "video";
            elseif ($attachment instanceof Audio) $type = "audio";
            elseif ($attachment instanceof Document) $type = "doc";
            $grouped[$type][] = $attachment;
        }
        return $grouped;
    }

    public function getPostType(): string
    {
        return $this->data["post_type"] ?? "post";
    }

    public function isPinned(): bool
    {
        return (bool) ($this->data["is_pinned"] ?? false);
    }

    /**
     * Возвращает перепостнутую запись (copy_history).
     */
    public function getCopyHistory(): ?self
    {
        $history = $this->data["copy_history"] ?? null;

        return !empty($history) && isset($history[0])
            ? new self($history[0])
            : null;
    }

    public function getGeo(): ?array
    {
        return $this->data["geo"] ?? null;
    }

    public function getCopyright(): ?array
    {
        return $this->data["copyright"] ?? null;
    }

    public function canEdit(): bool
    {
        return (bool) ($this->data["can_edit"] ?? false);
    }

    public function canDelete(): bool
    {
        return (bool) ($this->data["can_delete"] ?? false);
    }

    public function getPostUrl(): string
    {
        return "https://vk.com/wall{$this->getOwnerId()}_{$this->getId()}";
    }

    /* ===== Методы, добавленные/обновлённые для совместимости ===== */

    public function getPlatform(): ?string
    {
        return $this->data["platform"] ?? null;
    }

    public function getPlatformDetails(): array
    {
        return [];
    }

    public function getRepostCount(): int
    {
        return $this->getRepostsCount();
    }

    public function canBePinnedBy($who): bool
    {
        return false;
    }

    public function canBeEditedBy($who): bool
    {
        return $this->canEdit();
    }

    /**
     * Загружает владельца стены (User или Club) по owner_id.
     *
     * @return User|Club|null
     */
    public function getWallOwner(): User|Club|null
    {
        $ownerId = $this->getOwnerId();

        if ($ownerId > 0) {
            return User::load($ownerId);
        } elseif ($ownerId < 0) {
            return Club::load(abs($ownerId));
        }

        return User::load(100);
    }

    public function isDeactivationMessage(): bool
    {
        return false;
    }

    public function isExplicit(): bool
    {
        return false;
    }

    public function isPostedOnBehalfOfGroup(): bool
    {
        return $this->getOwnerId() < 0;
    }

    public function isUpdateAvatarMessage(): bool
    {
        return false;
    }

    public function getOwnerPost(): ?self
    {
        return null;
    }

    public function getTargetWall(): int
    {
        return $this->getOwnerId();
    }

    public function getVirtualId(): int
    {
        return $this->getId();
    }

    public function isAd(): bool
    {
        return false;
    }

    public function hasSource(): bool
    {
        return false;
    }

    public function getSource(bool $raw = false): ?string
    {
        return null;
    }

    public function isSigned(): bool
    {
        return false;
    }

    /**
     * VK API не предоставляет время редактирования.
     */
    public function getEditTime(): ?\openvk\Web\Util\DateTime
    {
        return null;
    }

    public function getPostSourceInfo(): ?string
    {
        return null;
    }

    public function getVkApiType(): string
    {
        $type = 'post';

        return $type;
    }

    public function getPageURL(): string
    {
        return "/wall" . $this->getPrettyId();
    }

}
