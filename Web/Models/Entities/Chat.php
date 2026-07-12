<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;

class Chat extends RowModel
{
    protected $tableName = "chats";

    public function getChatId(): int
    {
        return (int) ($this->getRecord()->chat_id ?? 0);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? "";
    }

    public function getDescription(): string
    {
        return $this->getRecord()->description ?? "";
    }

    public function getPhotoId(): ?int
    {
        $photoId = $this->getRecord()->photo_id;
        return $photoId !== null ? (int) $photoId : null;
    }

    public function getPhoto(): ?Photo
    {
        $photoId = $this->getPhotoId();
        if ($photoId === null) {
            return null;
        }

        return Photo::getById($photoId);
    }

    public function hasPhoto(): bool
    {
        return $this->getPhotoId() !== null;
    }

    public function setChatId(int $chatId): void
    {
        $this->stateChanges("chat_id", $chatId);
    }

    public function setTitle(string $title): void
    {
        $this->stateChanges("title", $title);
    }

    public function setDescription(string $description): void
    {
        $this->stateChanges("description", $description);
    }

    public function setPhotoId(?int $photoId): void
    {
        $this->stateChanges("photo_id", $photoId);
    }
}