<?php

declare(strict_types=1);

namespace openvk\Web\Models\VideoDrivers;

abstract class VideoDriver
{
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    abstract public function getThumbnailURL(): string;

    abstract public function getURL(): string;

    abstract public function getEmbed(string $w = "600", string $h = "340"): string;
}
