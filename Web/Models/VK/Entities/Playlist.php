<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

use openvk\VKAPIClient\VKAPIClient;

class Playlist extends VkEntity
{
    public function getVirtualId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getName(): string
    {
        return $this->data["title"] ?? "";
    }

    public function getTitle(): string
    {
        return $this->getName();
    }

    public function getDescription(): ?string
    {
        return $this->data["description"] ?? null;
    }

    public function getDescriptionHTML(): ?string
    {
        $desc = $this->getDescription();

        return $desc !== null ? htmlspecialchars($desc, ENT_DISALLOWED | ENT_XHTML) : null;
    }

    public function getMetaDescription(): string
    {
        $size  = $this->size();
        $len   = $this->getLength();
        $parts = [];

        if ($size > 0) {
            $parts[] = "$size tracks";
        }

        if ($len > 0) {
            $mins = (int) round($len / 60);
            $parts[] = "$mins min";
        }

        return implode(" • ", $parts);
    }

    public function size(): int
    {
        return (int) ($this->data["count"] ?? 0);
    }

    public function getLength(): int
    {
        return (int) ($this->data["duration"] ?? 0);
    }

    public function getLengthInMinutes(): int
    {
        return (int) round($this->getLength() / 60, PHP_ROUND_HALF_DOWN);
    }

    public function getFormattedLength(): string
    {
        return gmdate("i:s", $this->getLength());
    }

    public function getCoverPhoto(): ?Photo
    {
        $photoData = $this->data["photo"] ?? null;
        if (!$photoData) {
            return null;
        }

        // VK API returns photo in { photo: { id, owner_id, sizes: [...] } } format
        return new Photo($photoData);
    }

    public function getCoverURL(string $size = "normal"): ?string
    {
        $photo = $this->getCoverPhoto();
        $photo = null;
        if ($photo) {
            return $photo->getURLBySizeId($size);
        }

        // Fallback: thumb field
        $thumb = $this->data["photo"] ?? [];
        if (!empty($thumb)) {
            return match ($size) {
                "miniscule" => $thumb["photo_34"] ?? $thumb["photo_68"] ?? null,
                "tiny"      => $thumb["photo_68"] ?? $thumb["photo_135"] ?? null,
                "xsmall"    => $thumb["photo_135"] ?? $thumb["photo_270"] ?? null,
                "small"     => $thumb["photo_270"] ?? $thumb["photo_300"] ?? null,
                "medium"    => $thumb["photo_300"] ?? $thumb["photo_600"] ?? null,
                "normal"    => $thumb["photo_600"] ?? $thumb["photo_1200"] ?? null,
                "original"  => $thumb["photo_1200"] ?? null,
                default     => $thumb["photo_600"] ?? $thumb["photo_300"] ?? null,
            };
        }

        return "/assets/packages/static/openvk/img/song.jpg";
    }

    public function getURL(): string
    {
        return "/playlist" . $this->getPrettyId();
    }

    public function getPrettyId(): string
    {
        return $this->getOwnerId() . "_" . $this->getId();
    }

    public function getAudios(int $offset = 0, ?int $limit = null, ?int $shuffleSeed = null): array
    {
        $limit ??= 10;

        try {
            $response = VKAPIClient::i()->call("audio.get", [
                "owner_id" => $this->getOwnerId(),
                "playlist_id" => $this->getId(),
                "count" => min($limit, 200),
                "offset" => $offset,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $audios = [];
        foreach ($response["items"] ?? [] as $item) {
            $audios[] = new Audio($item);
        }

        return $audios;
    }

    public function fetch(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        return $this->getAudios($offset, $perPage);
    }

    public function isUnlisted(): bool
    {
        return false;
    }

    public function canBeModifiedBy($who = null): bool
    {
        return false;
    }

    public function canBeViewedBy($who = null): bool
    {
        return true;
    }

    public function isBookmarkedBy($entity = null): bool
    {
        return false;
    }

    public function hasAudio($audio): bool
    {
        return false;
    }

    public function getListens(): int
    {
        return $this->data["listens"] ?? 0;
    }

    public function getCreationTime(): \openvk\Web\Util\DateTime
    {
        return new \openvk\Web\Util\DateTime((int) ($this->data["create_time"] ?? 0));
    }

    public function getPublicationTime(): \openvk\Web\Util\DateTime
    {
        return $this->getCreationTime();
    }

    public function getEditTime(): ?\openvk\Web\Util\DateTime
    {
        $updateTime = $this->data["update_time"] ?? null;
        if (!$updateTime) {
            return null;
        }

        return new \openvk\Web\Util\DateTime((int) $updateTime);
    }
}
