<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-видео — имитация openvk\Web\Models\Entities\Video.
 * Данные из VK API (attachment video).
 *
 * VK API возвращает видео в формате:
 * { "id": 1, "owner_id": -1, "title": "...", "description": "...",
 *   "duration": 120, "photo_130": "...", "photo_320": "...",
 *   "photo_800": "...", "date": 123, "views": 100,
 *   "player": "https://vk.com/video_ext.php?...", "platform": "..." }
 */
class Video extends VkEntity
{
    public function getVirtualId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getPrettyId(): string
    {
        return $this->getOwnerId() . "_" . $this->getVirtualId();
    }

    /**
     * Возвращает название видео.
     */
    public function getName(): string
    {
        return $this->data["title"] ?? "";
    }

    public function getTitle(): string
    {
        return $this->getName();
    }

    public function getWidth(): int
    {
        return (int) ($this->data["width"] ?? 0);
    }

    public function getHeight(): int
    {
        return (int) ($this->data["height"] ?? 0);
    }

    public function getDimensions(): array
    {
        return [$this->getWidth(), $this->getHeight()];
    }

    /**
     * Возвращает описание.
     */
    public function getDescription(): string
    {
        return $this->data["description"] ?? "";
    }

    /**
     * Длительность в секундах.
     */
    public function getDuration(): int
    {
        return (int) ($this->data["duration"] ?? 0);
    }

    public function getLength(): int
    {
        return $this->getDuration();
    }

    /**
     * Возвращает URL обложки по размеру.
     */
    public function getPhotoBySize(string $size = "320"): string
    {
        return match ($size) {
            "130" => $this->data["photo_130"] ?? "",
            "320" => $this->data["photo_320"] ?? "",
            "800" => $this->data["photo_800"] ??
                ($this->data["photo_320"] ?? ""),
            default => $this->data["photo_320"] ?? "",
        };
    }

    public function getPhoto130(): string
    {
        return $this->data["photo_130"] ?? "";
    }

    public function getPhoto320(): string
    {
        return $this->data["photo_320"] ?? "";
    }

    public function getPhoto800(): string
    {
        return $this->data["photo_800"] ?? "";
    }

    public function getDate(): int
    {
        return (int) ($this->data["date"] ?? 0);
    }

    public function getViews(): int
    {
        return (int) ($this->data["views"] ?? 0);
    }

    public function getPlayer(): string
    {
        return $this->data["player"] ?? "";
    }

    public function getPlatform(): ?string
    {
        return $this->data["platform"] ?? null;
    }

    public function getAccessKey(): ?string
    {
        return $this->data["access_key"] ?? null;
    }

    /**
     * URL превью видео.
     */
    public function getThumbnailURL(): string
    {
        return $this->data["image"] ? end($this->data["image"])["url"] : null;
    }

    public function getPublicationTime(): \openvk\Web\Util\DateTime
    {
        return new \openvk\Web\Util\DateTime((int) ($this->data["date"] ?? 0));
    }

    public function getCommentsCount(): int
    {
        return (int) ($this->data["comments"] ?? 0);
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function getType(): int
    {
        $platform = $this->data["platform"] ?? "";
        if ($platform === "youtube" || !empty($this->data["player"])) {
            return 2; // EMBED
        }

        return 1; // DIRECT
    }

    public function getFormattedLength(): string
    {
        return gmdate("i:s", $this->getDuration());
    }

    public function getVideoDriver(): ?\openvk\Web\Models\VideoDrivers\VideoDriver
    {
        $platform = $this->data["platform"] ?? "";
        $player   = $this->data["player"] ?? "";

        // YouTube: use existing driver
        if ($platform === "youtube") {
            $url = $this->data["url"] ?? $player;
            $ytId = "";
            if (preg_match("%(?:youtube\\.com/watch\\?v=|youtu\\.be/)([A-z0-9_-]+)%", $url, $m)) {
                $ytId = $m[1];
            }

            if ($ytId) {
                return new \openvk\Web\Models\VideoDrivers\YouTubeVideoDriver($ytId);
            }
        }

        // Has VK player URL → use VKVideoDriver (iframe embed)
        if (!empty($player)) {
            return new \openvk\Web\Models\VideoDrivers\VKVideoDriver(
                $player,
                $this->getPhoto320(),
                $this->data["url"] ?? "",
            );
        }

        return null;
    }

    public function isProcessed(): bool
    {
        return true;
    }

    public function toggleLike($user): bool
    {
        $isLiked = $this->hasLikeFrom($user);
        try {
            if ($isLiked) {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.delete", [
                    "type" => "video",
                    "owner_id" => $this->getOwnerId(),
                    "item_id" => $this->getId(),
                ]);
                return false;
            } else {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.add", [
                    "type" => "video",
                    "owner_id" => $this->getOwnerId(),
                    "item_id" => $this->getId(),
                ]);
                return true;
            }
        } catch (\Throwable) {
            return $isLiked;
        }
    }
}
