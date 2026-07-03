<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Entities;

/**
 * VK-аудио — имитация openvk\Web\Models\Entities\Audio.
 *
 * ВНИМАНИЕ: VK API отключил аудио в 2022 году.
 * Этот класс-заглушка возвращает заглушечные данные.
 *
 * Если у вас есть токен с правом audio, данные будут из
 * { "id": 1, "owner_id": -1, "artist": "...", "title": "...",
 *   "duration": 180, "url": "...", "genre_id": 1 }
 */
class Audio extends VkEntity
{
    public function getVirtualId(): int
    {
        return (int) ($this->data["id"] ?? 0);
    }

    public function getPrettyId(): string
    {
        return $this->getOwnerId() . "_" . $this->getVirtualId();
    }

    public function getArtist(): string
    {
        return $this->data["artist"] ??
            ($this->data["performer"] ?? "Unknown Artist");
    }

    /**
     * @deprecated Используйте getArtist()
     */
    public function getPerformer(): string
    {
        return $this->getArtist();
    }

    public function getPerformers()
    {
        return explode(",", $this->getArtist());
    }

    public function getTitle(): string
    {
        bdump($this->data);
        return $this->data["title"] ?? ($this->data["name"] ?? "Unknown Track");
    }

    public function getName(): string
    {
        return $this->getTitle();
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

    public function getFormattedLength(): string
    {
        $len  = $this->getLength();
        $mins = floor($len / 60);
        $secs = $len - ($mins * 60);

        return (
            str_pad((string) $mins, 2, "0", STR_PAD_LEFT)
            . ":" .
            str_pad((string) $secs, 2, "0", STR_PAD_LEFT)
        );
    }

    public function getSegmentSize(): float
    {
        return 5;
    }

    public function getListens(): int
    {
        return 100;
    }

    public function isUnlisted(): bool
    {
        return false;
    }

    /**
     * Жанр (VK API genre_id).
     */
    public function getGenreId(): int
    {
        return (int) ($this->data["genre_id"] ?? 18); // 18 = Other
    }

    public function getUrl(): string
    {
        return $this->data["url"] ?? "";
    }

    public function getLink(): string
    {
        return $this->data["url"] ?? "";
    }

    /**
     * Есть ли текст (lyrics).
     */
    public function hasLyrics(): bool
    {
        return (bool) ($this->data["lyrics_id"] ?? false);
    }

    public function getLyrics(): ?string
    {
        return $this->data["lyrics"] ?? null;
    }

    public function isWithdrawn(): bool
    {
        return false;
    }

    public function isInLibraryOf($user): bool
    {
        return (bool) ($this->data["is_added"] ?? false);
    }

    public function getOriginalURL(): ?string
    {
        return $this->data["url"] ?? null;
    }

    public function getDownloadName(): string
    {
        return $this->getName();
    }

    public function getAlbumId(): ?int
    {
        return isset($this->data["album_id"])
            ? (int) $this->data["album_id"]
            : null;
    }

    public function getGenre(): string
    {
        $genres = [
            1 => "Rock",
            2 => "Pop",
            3 => "Rap",
            4 => "Easy Listening",
            5 => "House",
            6 => "Instrumental",
            7 => "Metal",
            8 => "Dubstep",
            9 => "Drum & Bass",
            10 => "Trance",
            11 => "Chanson",
            12 => "Ethnic",
            13 => "Acoustic",
            14 => "Reggae",
            15 => "Classical",
            16 => "Indie Pop",
            17 => "Speech",
            18 => "Other",
            19 => "Alternative",
            20 => "Disco",
            21 => "Jazz & Blues",
        ];

        return $genres[$this->getGenreId()] ?? "Other";
    }

    public function getKeys(): ?object
    {
        return null;
    }

    public function isAvailable(): bool
    {
        return !empty($this->data["url"]);
    }

    public function isExplicit(): bool
    {
        return (bool) ($this->data["explicit"] ?? false);
    }

    public function canBeModifiedBy($who): bool
    {
        return false;
    }
}
