<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Nette\InvalidStateException as ISE;

abstract class Media extends Postable
{
    protected $fileExtension = "oct"; #octet stream xddd
    protected $upperNodeReferenceColumnName = "owner";
    
    function __destruct()
    {
        #Remove data, if model wasn't presisted
        if(isset($this->changes["hash"]))
            unlink($this->pathFromHash($this->changes["hash"]));
    }
    
    protected function getBaseDir(): string
    {
        $uploadSettings = OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"];
        if($uploadSettings["mode"] === "server" && $uploadSettings["server"]["kind"] === "cdn")
            return $uploadSettings["server"]["directory"];
        else
            return OPENVK_ROOT . "/storage/";
    }
    
    abstract protected function saveFile(string $filename, string $hash): bool;
    
    protected function pathFromHash(string $hash): string
    {
        $dir = $this->getBaseDir() . substr($hash, 0, 2);
        if(!is_dir($dir))
            mkdir($dir);
        
        return "$dir/$hash." . $this->fileExtension;
    }
    
    function getFileName(): string
    {
        return $this->pathFromHash($this->getRecord()->hash);
    }
    
    function getURL(): string
    {
        $hash = $this->getRecord()->hash;
        
        switch(OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["mode"]) {
            default:
            case "default":
            case "basic":
                return "http://" . $_SERVER['HTTP_HOST'] . "/blob_" . substr($hash, 0, 2) . "/$hash.$this->fileExtension";
            break;
            case "accelerated":
                return "http://" . $_SERVER['HTTP_HOST'] . "/openvk-datastore/$hash.$this->fileExtension";
            break;
            case "server":
                $settings = (object) OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["server"];
                return (
                    $settings->protocol .
                    "://" . $settings->host .
                    $settings->path .
                    substr($hash, 0, 2) . "/$hash.$this->fileExtension"
                );
            break;
        }
    }
    
    function getDescription(): ?string
    {
        return $this->getRecord()->description;
    }
    
    function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }
    
    function setHash(string $hash): void
    {
        throw new ISE("Setting file hash manually is forbidden");
    }
    
    function setFile(array $file): void
    {
        if($file["error"] !== UPLOAD_ERR_OK)
            throw new ISE("File uploaded is corrupted");
        
        $hash = hash_file("whirlpool", $file["tmp_name"]);
        $this->saveFile($file["tmp_name"], $hash);
        
        $this->stateChanges("hash", $hash);
    }
}
