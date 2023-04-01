<?php declare(strict_types=1);
namespace openvk\Web\Models\VideoDrivers;

final class YouTubeVideoDriver extends VideoDriver
{
    function getThumbnailURL(): string
    {
        return "https://img.youtube.com/vi/$this->id/mqdefault.jpg";
    }
    
    function getURL(): string
    {
        return "https://youtu.be/$this->id";
    }
    
    function getEmbed(string $w = "600", string $h = "340"): string
    {
        return <<<CODE
        <iframe
               width="$w"
               height="$h"
               src="https://www.youtube-nocookie.com/embed/$this->id"
               frameborder="0"
               sandbox="allow-same-origin allow-scripts allow-popups"
               allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
               allowfullscreen></iframe>
CODE;
    }
}
