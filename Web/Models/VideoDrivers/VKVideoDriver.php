<?php

declare(strict_types=1);

namespace openvk\Web\Models\VideoDrivers;

class VKVideoDriver extends VideoDriver
{
    private string $playerUrl;
    private string $thumbUrl;
    private string $videoUrl;

    public function __construct(string $playerUrl, string $thumbUrl = "", string $videoUrl = "")
    {
        parent::__construct($playerUrl);
        $this->playerUrl = $playerUrl;
        $this->thumbUrl  = $thumbUrl;
        $this->videoUrl  = $videoUrl;
    }

    public function getThumbnailURL(): string
    {
        return $this->thumbUrl;
    }

    public function getURL(): string
    {
        return $this->videoUrl ?: $this->playerUrl;
    }

    public function getEmbed(string $w = "600", string $h = "340"): string
    {
        return <<<CODE
            <iframe
                width="$w"
                height="$h"
                src="{$this->playerUrl}"
                frameborder="0"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
        CODE;
    }
}
