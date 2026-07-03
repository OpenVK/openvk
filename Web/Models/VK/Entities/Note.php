<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-заметка — имитация openvk\Web\Models\Entities\Note.
 *
 * VK API: notes.getById возвращает:
 * { "id": 1, "owner_id": 1, "title": "...", "text": "...",
 *   "date": 123, "comments": 5, "read_state": 1, "view_url": "..." }
 */
class Note
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

    public function getVirtualId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getPrettyId(): string
    {
        return $this->getOwnerId() . "_" . $this->getVirtualId();
    }

    public function getTitle(): string
    {
        return $this->data["title"] ?? "";
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    /**
     * VK API возвращает полный HTML текст заметки.
     */
    public function getText(): string
    {
        return $this->data["text"] ?? "";
    }

    /**
     * VK API возвращает уже готовый HTML.
     */
    public function getHTML(): string
    {
        return $this->data["text"] ?? "";
    }

    public function getDate(): int
    {
        return (int) ($this->data["date"] ?? 0);
    }

    public function getPublicationTime(): \openvk\Web\Util\DateTime
    {
        return new \openvk\Web\Util\DateTime($this->getDate());
    }

    public function getCreationTime(): int
    {
        return $this->getDate();
    }

    public function getCommentsCount(): int
    {
        return (int) ($this->data["comments"] ?? 0);
    }

    /**
     * Ссылка на заметку на vk.com.
     */
    public function getViewUrl(): string
    {
        return $this->data["view_url"] ?? "";
    }

    public function getURL(): string
    {
        return $this->getViewUrl();
    }

    public function getOwner(): ?User
    {
        if (!$this->getOwnerId()) {
            return null;
        }

        return User::load($this->getOwnerId());
    }

    public function isDeleted(): bool
    {
        return false;
    }

    public function getRawData(): array
    {
        return $this->data;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
