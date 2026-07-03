<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-фото — имитация openvk\Web\Models\Entities\Photo.
 * Данные из VK API (attachment photo).
 *
 * VK API возвращает фото в формате:
 * { "id": 1, "owner_id": -1, "album_id": 0, "sizes": [...], "text": "", "date": 123 }
 */
class Photo extends VkEntity
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
     * Возвращает URL по размеру.
     * В VK API sizes содержат type и url.
     */
    public function getURLBySizeId(string $sizeId): string
    {
        $sizes = $this->data["sizes"] ?? [];

        // Маппинг размеров OpenVK → VK API
        $vkType = match ($sizeId) {
            "miniscule" => "s",
            "small"     => "m",
            "normal"    => "x",
            "large"     => "y",
            "original"  => "w",
            default     => "x",
        };

        // Сначала ищем точный тип
        foreach ($sizes as $size) {
            if (($size["type"] ?? "") === $vkType) {
                return $size["url"] ?? "";
            }
        }

        // Если не нашли — возвращаем последний (самый большой)
        $last = end($sizes);

        return $last["url"] ?? "";
    }

    public function getURL(): string
    {
        return $this->getURLBySizeId("normal");
    }

    public function getText(): string
    {
        return $this->data["text"] ?? "";
    }

    public function getDate(): int
    {
        return (int) ($this->data["date"] ?? 0);
    }

    public function getWidth(): int
    {
        $sizes = $this->data["sizes"] ?? [];
        $last  = end($sizes);

        return (int) ($last["width"] ?? 0);
    }

    public function getHeight(): int
    {
        $sizes = $this->data["sizes"] ?? [];
        $last  = end($sizes);

        return (int) ($last["height"] ?? 0);
    }

    public function getDimensions(): array
    {
        return [$this->getWidth(), $this->getHeight()];
    }

    public function getAccessKey(): ?string
    {
        return $this->data["access_key"] ?? null;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isAnonymous(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return $this->getText();
    }

    public function getOwner(): ?\openvk\Web\Models\VK\Entities\User
    {
        if ($this->getOwnerId() > 0) {
            return \openvk\Web\Models\VK\Entities\User::load($this->getOwnerId());
        }

        return null;
    }

    public function getAvatarLink(): string
    {
        return "/photo" . $this->getPrettyId();
    }

    public function toggleLike($user): bool
    {
        $isLiked = $this->hasLikeFrom($user);
        try {
            if ($isLiked) {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.delete", [
                    "type" => "photo",
                    "owner_id" => $this->getOwnerId(),
                    "item_id" => $this->getId(),
                ]);
                return false;
            } else {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.add", [
                    "type" => "photo",
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
