<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Nette\InvalidStateException as ISE;
use BoyHagemann\Wave\Wave;

define("AUDIOS_FRIENDLY_ERROR", "Audio uploads are disabled on this instance :<", false);

class Audio extends Media
{
    protected $tableName     = "audios";
    protected $fileExtension = "mpeg3";
    
    protected function saveFile(string $filename, string $hash): bool
    {
        if(!is_dir($dirId = OPENVK_ROOT . "/storage/" . substr($hash, 0, 2)))
            mkdir($dirId);
        
        try {
            $getID3 = new \getID3;
            $meta   = $getID3->analyze($filename);
            if(isset($meta["error"]))
                throw new ISE(implode(", ", $meta["error"]));
            
            $this->setPerformer("Неизвестно");
            $this->setName("Без названия");
        } catch(\Exception $ex) {
            exit("Хакеры? Интересно...");
        }
        
        return rename($filename, OPENVK_ROOT . "/storage/" . substr($hash, 0, 2) . "/$hash.mpeg3");
    }
    
    function getName(): string
    {
        return $this->getRecord()->name;
    }
    
    function getPerformer(): string
    {
        return $this->getRecord()->performer;
    }
    
    function getGenre(): string
    {
        return $this->getRecord()->genre;
    }
    
    function getLyrics(): ?string
    {
        return $this->getRecord()->lyrics;
    }
    
    function getCanonicalName(): string
    {
        return $this->getRecord()->performer . " — " . $this->getRecord()->name;
    }
    
    function wire(): void
    {
        \Chandler\Database\DatabaseConnection::i()->getContext()->table("audio_relations")->insert([
            "user"  => $this->getRecord()->owner,
            "audio" => $this->getId(),
        ]);
    }
}
