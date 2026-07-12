<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Photos;


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

        $photoRepo = new Photos();

        return $photoRepo->get($photoId);
    }

    public function getPhotoURL(string $size = "miniscule"): string | null
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        $photo = $this->getPhoto();
        if (is_null($photo)) {
            return null; 
        }

        return $photo->getURLBySizeId($size);
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