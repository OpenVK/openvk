<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\Shell\Shell;
use openvk\Web\Util\Shell\Shell\Exceptions\{ShellUnavailableException, UnknownCommandException};
use openvk\Web\Models\VideoDrivers\VideoDriver;
use Nette\InvalidStateException as ISE;

define("VIDEOS_FRIENDLY_ERROR", "Uploads are disabled on this instance :<", false);

class Video extends Media
{
    const TYPE_DIRECT = 0;
    const TYPE_EMBED  = 1;
    
    protected $tableName     = "videos";
    protected $fileExtension = "ogv";
    
    protected function saveFile(string $filename, string $hash): bool
    {
        if(!Shell::commandAvailable("ffmpeg") || !Shell::commandAvailable("ffprobe"))
            exit(VIDEOS_FRIENDLY_ERROR);
        
        $error     = NULL;
        $streams   = Shell::ffprobe("-i", $filename, "-show_streams", "-select_streams v", "-loglevel error")->execute($error);
        if($error !== 0)
            throw new \DomainException("$filename is not a valid video file");
        else if(empty($streams) || ctype_space($streams))
            throw new \DomainException("$filename does not contain any video streams");
        
        $durations = [];
        preg_match('%duration=([0-9\.]++)%', $streams, $durations);
        if(sizeof($durations[1]) === 0)
            throw new \DomainException("$filename does not contain any meaningful video streams");
        
        foreach($durations[1] as $duration)
            if(floatval($duration) < 1.0)
                throw new \DomainException("$filename does not contain any meaningful video streams");
        
        try {
            if(!is_dir($dirId = $this->pathFromHash($hash)))
                mkdir($dirId);
            
            $dir = $this->getBaseDir();
            Shell::bash(__DIR__ . "/../shell/processVideo.sh", OPENVK_ROOT, $filename, $dir, $hash)->start(); #async :DDD
        } catch(ShellUnavailableException $suex) {
            exit(OPENVK_ROOT_CONF["openvk"]["debug"] ? "Shell is unavailable" : VIDEOS_FRIENDLY_ERROR);
        } catch(UnknownCommandException $ucex) {
            exit(OPENVK_ROOT_CONF["openvk"]["debug"] ? "bash is not installed" : VIDEOS_FRIENDLY_ERROR);
        }
        
        usleep(200100);
        return true;
    }
    
    function getName(): string
    {
        return $this->getRecord()->name;
    }
    
    function getType(): int
    {
        if(!is_null($this->getRecord()->hash))
            return Video::TYPE_DIRECT;
        else if(!is_null($this->getRecord()->link))
            return Video::TYPE_EMBED;
    }
    
    function getVideoDriver(): ?VideoDriver
    {
        if($this->getType() !== Video::TYPE_EMBED)
            return NULL;
        
        [$videoDriver, $pointer] = explode(":", $this->getRecord()->link);
        $videoDriver = "openvk\\Web\\Models\\VideoDrivers\\$videoDriver" . "VideoDriver";
        if(!class_exists($videoDriver))
            return NULL;
        
        return new $videoDriver($pointer);
    }
    
    function getThumbnailURL(): string
    {
        if($this->getType() === Video::TYPE_DIRECT) {
            return preg_replace("%\.[A-z]++$%", ".gif", $this->getURL());
        } else {
            return $this->getVideoDriver()->getThumbnailURL();
        }
    }

    function getOwnerVideo(): int
    {
        return $this->getRecord()->owner;
    }
    
    function setLink(string $link): string
    {
        if(preg_match(file_get_contents(__DIR__ . "/../VideoDrivers/regex/youtube.txt"), $link, $matches)) {
            $pointer = "YouTube:$matches[1]";
        } else if(preg_match(file_get_contents(__DIR__ . "/../VideoDrivers/regex/vimeo.txt"), $link, $matches)) {
            $pointer = "Vimeo:$matches[1]";
        } else {
            throw new ISE("Invalid link");
        }
        
        $this->stateChanges("link", $pointer);
        
        return $pointer;
    }

    function isDeleted(): bool
    {
        if ($this->getRecord()->deleted == 1)
            return TRUE;
        else
            return FALSE;
    }

    function deleteVideo(): void 
    {
        $this->setDeleted(1);
        $this->unwire();
        $this->save();
    }
    
    static function fastMake(int $owner, string $description = "", array $file, bool $unlisted = true): Video
    {
        $video = new Video;
        $video->setOwner($owner);
        $video->setName("Unnamed Video.ogv");
        $video->setDescription(ovk_proc_strtr($description, 300));
        $video->setCreated(time());
        $video->setFile($file);
        $video->setUnlisted($unlisted);
        $video->save();
        
        return $video;
    }
}
