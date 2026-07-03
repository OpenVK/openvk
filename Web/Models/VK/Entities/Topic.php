<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-тема обсуждения — имитация топика из board.getTopics.
 *
 * VK API board.getTopics возвращает:
 * { "id": 1, "title": "...", "comments": 5, "created": 123, "updated": 456,
 *   "is_closed": 0, "is_fixed": 1, "group_id": 1 }
 */
class Topic extends VkEntity
{
    public function getTitle(): string
    {
        return $this->data["title"] ?? "";
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    public function getCommentsCount(): int
    {
        return (int) ($this->data["comments"] ?? 0);
    }

    public function getPrettyId(): string
    {
        return $this->getGroupId() . "_" . $this->getId();
    }

    /**
     * Возвращает время создания темы как DateTime.
     */
    public function getCreationTime(): \DateTime
    {
        $timestamp = (int) ($this->data["created"] ?? 0);

        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);

        return $dt;
    }

    /**
     * Возвращает unix timestamp создания.
     */
    public function getCreated(): int
    {
        return (int) ($this->data["created"] ?? 0);
    }

    /**
     * Возвращает unix timestamp последнего обновления.
     */
    public function getUpdated(): int
    {
        return (int) ($this->data["updated"] ?? 0);
    }

    public function getVirtualId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getGroupId(): int
    {
        return -(int) ($this->data["group_id"] ?? 0);
    }

    /**
     * Возвращает положительный ID группы.
     */
    public function getPositiveGroupId(): int
    {
        return (int) ($this->data["group_id"] ?? 0);
    }

    public function isClosed(): bool
    {
        return (bool) ($this->data["is_closed"] ?? false);
    }

    public function isPinned(): bool
    {
        return (bool) ($this->data["is_fixed"] ?? false);
    }
}
