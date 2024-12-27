<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\{Clubs, Users, Photos};
use openvk\Web\Models\Entities\{Photo};

class Document extends Media
{
    protected $tableName     = "documents";
    protected $fileExtension = "gif";

    const VKAPI_TYPE_TEXT  = 1;
    const VKAPI_TYPE_ARCHIVE = 2;
    const VKAPI_TYPE_GIF   = 3;
    const VKAPI_TYPE_IMAGE = 4;
    const VKAPI_TYPE_AUDIO = 5;
    const VKAPI_TYPE_VIDEO = 6;
    const VKAPI_TYPE_BOOKS = 7;
    const VKAPI_TYPE_UNKNOWN = 8;

    const VKAPI_FOLDER_PRIVATE = 0;
    const VKAPI_FOLDER_STUDY   = 1;
    const VKAPI_FOLDER_BOOK    = 2;
    const VKAPI_FOLDER_PUBLIC  = 3;

    protected function pathFromHash(string $hash): string
    {
        $dir = $this->getBaseDir() . substr($hash, 0, 2);
        if(!is_dir($dir))
            mkdir($dir);
        
        return "$dir/$hash." . $this->getFileExtension();
    }

    protected function saveFile(string $filename, string $hash): bool
    {

        return true;
    }

    function getURL(): string
    {
        $hash = $this->getRecord()->hash;
        $filetype = $this->getFileExtension();
        
        switch(OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["mode"]) {
            default:
            case "default":
            case "basic":
                return "http://" . $_SERVER['HTTP_HOST'] . "/blob_" . substr($hash, 0, 2) . "/$hash.$filetype";
            break;
            case "accelerated":
                return "http://" . $_SERVER['HTTP_HOST'] . "/openvk-datastore/$hash.$filetype";
            break;
            case "server":
                $settings = (object) OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["server"];
                return (
                    $settings->protocol ?? ovk_scheme() .
                    "://" . $settings->host .
                    $settings->path .
                    substr($hash, 0, 2) . "/$hash.$filetype"
                );
            break;
        }
    }

    function hasPreview(): bool
    {
        return $this->getRecord()->preview != NULL;
    }
    
    function isOwnerHidden(): bool
    {
        return (bool) $this->getRecord()->owner_hidden;
    }

    function isCopy(): bool
    {
        return $this->getRecord()->copy_of != NULL;
    }

    function isLicensed(): bool
    {
        return false;
    }

    function isUnsafe(): bool
    {
        return false;
    }

    function getFileExtension(): string
    {
        return $this->getRecord()->format;
    }

    function getPrettyId(): string
    {
        return $this->getVirtualId() . "_" . $this->getId();
    }

    function getOriginal(): Document
    {
        return $this->getRecord()->copy_of;
    }

    function getName(): string 
    {
        return $this->getRecord()->name;
    }

    function getOriginalName(): string 
    {
        return $this->getRecord()->original_name;
    }

    function getVKAPIType(): int
    {
        return $this->getRecord()->type;
    }

    function getFolder(): int
    {
        return $this->getRecord()->folder_id;
    }

    function getTags(): array
    {
        return explode(",", $this->getRecord()->tags);
    }

    function getFilesize(): int
    {
        return $this->getRecord()->filesize;
    }

    function getPreview(): ?RowModel
    {
        $preview_array = $this->getRecord()->preview;
        $preview  = explode(",", $this->getRecord()->preview)[0];
        $model    = NULL;
        $exploded = explode("_", $preview);

        switch($exploded[0]) {
            case "photo":
                $model = (new Photos)->get((int)$exploded[1]);
                break;
        }

        return $model;
    }

    function getOwnerID(): int
    {
        return $this->getRecord()->owner;
    }

    function toApiPreview(): object
    {
        $preview = $this->getPreview();
        if($preview instanceof Photo) {
            return (object)[
                "photo" => [
                    "sizes" => array_values($preview->getVkApiSizes()),
                ],
            ];
        }
    }

    function canBeModifiedBy(User $user = NULL): bool
    {
        if(!$user)
            return false;

        if($this->getOwnerID() < 0)
            return (new Clubs)->get(abs($this->getOwnerID()))->canBeModifiedBy($user);
        
        return $this->getOwnerID() === $user->getId();
    }

    function toVkApiStruct(?User $user = NULL): object 
    {
        $res = new \stdClass;
        $res->id = $this->getId();
        $res->owner_id = $this->getVirtualId();
        $res->title = $this->getName();
        $res->size  = $this->getFilesize();
        $res->ext   = $this->getFileExtension();
        $res->url   = $this->getURL();
        $res->date  = $this->getPublicationTime()->timestamp();
        $res->type  = $this->getVKAPIType();
        $res->is_licensed = (int) $this->isLicensed();
        $res->is_unsafe   = (int) $this->isUnsafe();
        $res->folder_id   = (int) $this->getFolder();
        $res->private_url = "";
        if($user) {
            $res->can_manage = $this->canBeModifiedBy($user);
        }

        if($this->hasPreview()) {
            $res->preview = $this->toApiPreview();
        }

        return $res;
    }
}
