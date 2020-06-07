<?php declare(strict_types=1);
namespace openvk\Web\Models\VideoDrivers;

final class YouTubeVideoDriver extends VideoDriver
{
    function getThumbnailURL(): string
    {
        return "https://img.youtube.com/vi/$this->id/mq3.jpg";
    }
    
    function getURL(): string
    {
        return "https://youtu.be/$this->id";
    }
    
    function getEmbed(): string
    {
        return <<<CODE
        <iframe
               width="600"
               height="340"
               src="https://www.youtube.com/embed/$this->id"
               frameborder="0"
               sandbox="allow-same-origin allow-scripts allow-popups"
               allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
               allowfullscreen></iframe>
CODE;
    }
}
