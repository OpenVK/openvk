<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

use openvk\VKAPIClient\VKAPIClient;

/**
 * VK-альбом — имитация openvk\Web\Models\Entities\Album.
 *
 * VK API: photos.getAlbums возвращает:
 * { "id": 1, "owner_id": 1, "title": "...", "description": "...",
 *   "size": 10, "created": 123, "updated": 456,
 *   "thumb_src": "...", "privacy_view": [...] }
 */
class Album extends VkEntity
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getOwnerId(): int
    {
        return (int) ($this->data["owner_id"] ?? 0);
    }

    public function getPrettyId(): string
    {
        return $this->getOwnerId() . "_" . $this->getId();
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

    public function getSize(): int
    {
        return (int) ($this->data["size"] ?? 0);
    }

    public function getPhotosCount(): int
    {
        return $this->getSize();
    }

    /**
     * URL обложки альбома.
     */
    public function getCoverURL(): ?string
    {
        return $this->getCoverPhoto() ? $this->getCoverPhoto()->getURLBySizeId('normal') : null;
    }

    /**
     * Фото обложки (заглушка).
     */
    public function getCoverPhoto(): ?Photo
    {
        return new Photo([
            "sizes" => [
                [
                    "name" => "q",
                    "url"  => $this->data["thumb_src"]
                ]
            ]
        ]);
    }

    /**
     * Загружает фото из альбома.
     * VK: photos.get
     *
     * @return Photo[]
     */
    public function getPhotos(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= 10;
        $offset = $perPage * ($page - 1);

        try {
            $response = VKAPIClient::i()->call("photos.get", [
                "owner_id" => $this->getOwnerId(),
                "album_id" => $this->getId(),
                "offset" => $offset,
                "count" => min($perPage, 200),
            ]);
        } catch (\Throwable) {
            return [];
        }

        $photos = [];
        foreach ($response["items"] ?? [] as $item) {
            $photos[] = new Photo($item);
        }

        return $photos;
    }

    /**
     * Заглушка для MediaCollection методов.
     */
    public function isCreatedBySystem(): bool
    {
        return false;
    }

    public function getEditTime(): ?\openvk\Web\Util\DateTime
    {
        if (empty($this->data["updated"])) {
            return null;
        }

        return new \openvk\Web\Util\DateTime((int) $this->data["updated"]);
    }

    public function getPublicationTime(): \openvk\Web\Util\DateTime
    {
        return new \openvk\Web\Util\DateTime(
            (int) ($this->data["created"] ?? 0),
        );
    }

    public function isDeleted(): bool
    {
        return false;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
