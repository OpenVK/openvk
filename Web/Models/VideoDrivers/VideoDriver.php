<?php declare(strict_types=1);
namespace openvk\Web\Models\VideoDrivers;

abstract class VideoDriver
{
    protected $id;
    
    function __construct(string $id)
    {
        $this->id = $id;
    }
    
    abstract function getThumbnailURL(): string;
    
    abstract function getURL(): string;
    
    abstract function getEmbed(): string;
}
