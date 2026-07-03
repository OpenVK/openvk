<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-документ — имитация openvk\Web\Models\Entities\Document.
 * Данные из VK API (docs.getById / docs.get).
 *
 * VK API возвращает документ в формате:
 * { "id": 1, "owner_id": 1, "title": "...", "size": 123, "ext": "pdf",
 *   "url": "...", "date": 123, "type": 4, "access_key": "..." }
 */
class Document extends VkEntity
{
    public function getTitle(): string
    {
        return $this->data["title"] ?? "";
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    /**
     * Размер файла в байтах.
     */
    public function getSize(): int
    {
        return (int) ($this->data["size"] ?? 0);
    }

    public function getFileSize(): int
    {
        return $this->getSize();
    }

    public function getExtension(): string
    {
        return $this->data["ext"] ?? "";
    }

    public function getURL(): string
    {
        return $this->data["url"] ?? "";
    }

    /**
     * Возвращает unix timestamp создания документа.
     */
    public function getDate(): int
    {
        return (int) ($this->data["date"] ?? 0);
    }

    /**
     * Возвращает тип документа как строку.
     * В VK API тип — число, но здесь приводится к строке
     * для удобства (например: "doc", "gif", "image", "audio" и т.д.).
     */
    public function getType(): string
    {
        $type = $this->data["type"] ?? 0;

        return match ((int) $type) {
            1 => "text",
            2 => "archive",
            3 => "gif",
            4 => "image",
            5 => "audio",
            6 => "video",
            7 => "book",
            default => "doc",
        };
    }

    public function getAccessKey(): ?string
    {
        return $this->data["access_key"] ?? null;
    }

    public function isImage(): bool
    {
        return $this->getExtension() !== "gif"
            && in_array(mb_strtolower($this->getExtension()), [
                "jpg", "jpeg", "png", "webp", "bmp", "tiff", "psd",
            ]);
    }

    public function isGif(): bool
    {
        return mb_strtolower($this->getExtension()) === "gif";
    }
}
