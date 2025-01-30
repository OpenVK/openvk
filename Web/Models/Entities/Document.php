<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Repositories\{Clubs, Users, Photos};
use openvk\Web\Models\Entities\{Photo};
use openvk\Web\Models\RowModel;
use Nette\InvalidStateException as ISE;
use Chandler\Database\DatabaseConnection;

class Document extends Media
{
    protected $tableName     = "documents";
    protected $fileExtension = "gif";
    private $tmp_format = null;

    public const VKAPI_TYPE_TEXT  = 1;
    public const VKAPI_TYPE_ARCHIVE = 2;
    public const VKAPI_TYPE_GIF   = 3;
    public const VKAPI_TYPE_IMAGE = 4;
    public const VKAPI_TYPE_AUDIO = 5;
    public const VKAPI_TYPE_VIDEO = 6;
    public const VKAPI_TYPE_BOOKS = 7;
    public const VKAPI_TYPE_UNKNOWN = 8;

    public const VKAPI_FOLDER_PRIVATE = 0;
    public const VKAPI_FOLDER_STUDY   = 1;
    public const VKAPI_FOLDER_BOOK    = 2;
    public const VKAPI_FOLDER_PUBLIC  = 3;

    protected function pathFromHash(string $hash): string
    {
        $dir = $this->getBaseDir() . substr($hash, 0, 2);
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return "$dir/$hash." . $this->getFileExtension();
    }

    public function getURL(): string
    {
        $hash = $this->getRecord()->hash;
        $filetype = $this->getFileExtension();

        switch (OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["mode"]) {
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

    protected function saveFile(string $filename, string $hash): bool
    {
        move_uploaded_file($filename, $this->pathFromHash($hash));
        return true;
    }

    protected function makePreview(string $tmp_name, string $filename, int $owner): bool
    {
        $preview_photo = new Photo();
        $preview_photo->setOwner($owner);
        $preview_photo->setDescription("internal use");
        $preview_photo->setCreated(time());
        $preview_photo->setSystem(1);
        $preview_photo->setFile([
            "tmp_name" => $tmp_name,
            "error"    => 0,
        ]);
        $preview_photo->save();
        $this->stateChanges("preview", "photo_" . $preview_photo->getId());

        return true;
    }

    private function updateHash(string $hash): bool
    {
        $this->stateChanges("hash", $hash);

        return true;
    }

    public function setFile(array $file): void
    {
        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new ISE("File uploaded is corrupted");
        }

        $original_name = $file["name"];
        $file_format = end(explode(".", $original_name));
        $file_size   = $file["size"];
        $type        = Document::detectTypeByFormat($file_format);

        if (!$file_format) {
            throw new \TypeError("No file format");
        }

        if (!in_array(mb_strtolower($file_format), OPENVK_ROOT_CONF["openvk"]["preferences"]["docs"]["allowedFormats"])) {
            throw new \TypeError("Forbidden file format");
        }

        if ($file_size < 1 || $file_size > (OPENVK_ROOT_CONF["openvk"]["preferences"]["docs"]["maxSize"] * 1024 * 1024)) {
            throw new \ValueError("Invalid filesize");
        }

        $hash = hash_file("whirlpool", $file["tmp_name"]);
        $this->stateChanges("original_name", ovk_proc_strtr($original_name, 255));
        $this->tmp_format = mb_strtolower($file_format);
        $this->stateChanges("format", mb_strtolower($file_format));
        $this->stateChanges("filesize", $file_size);
        $this->stateChanges("hash", $hash);
        $this->stateChanges("access_key", bin2hex(random_bytes(9)));
        $this->stateChanges("type", $type);

        if (in_array($type, [3, 4])) {
            $this->makePreview($file["tmp_name"], $original_name, $file["preview_owner"]);
        }

        $this->saveFile($file["tmp_name"], $hash);
    }

    public function hasPreview(): bool
    {
        return $this->getRecord()->preview != null;
    }

    public function isOwnerHidden(): bool
    {
        return (bool) $this->getRecord()->owner_hidden;
    }

    public function isCopy(): bool
    {
        return $this->getRecord()->copy_of != null;
    }

    public function isLicensed(): bool
    {
        return false;
    }

    public function isUnsafe(): bool
    {
        return false;
    }

    public function isAnonymous(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return $this->getFolder() == Document::VKAPI_FOLDER_PRIVATE;
    }

    public function isImage(): bool
    {
        return in_array($this->getVKAPIType(), [3, 4]);
    }

    public function isBook(): bool
    {
        return in_array($this->getFileExtension(), ["pdf"]);
    }

    public function isAudio(): bool
    {
        return in_array($this->getVKAPIType(), [Document::VKAPI_TYPE_AUDIO]);
    }

    public function isGif(): bool
    {
        return $this->getVKAPIType() == 3;
    }

    public function isCopiedBy($user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->getRealId() === $this->getOwnerID()) {
            return true;
        }

        return DatabaseConnection::i()->getContext()->table("documents")->where([
            "owner"   => $user->getRealId(),
            "copy_of" => $this->getId(),
            "deleted" => 0,
        ])->count() > 0;
    }

    public function copy(User $user): Document
    {
        $item = DatabaseConnection::i()->getContext()->table("documents")->where([
            "owner"   => $user->getId(),
            "copy_of" => $this->getId(),
        ]);
        if ($item->count() > 0) {
            $older = new Document($item->fetch());
        }

        $this_document_array = $this->getRecord()->toArray();

        $new_document = new Document();
        $new_document->setOwner($user->getId());
        $new_document->updateHash($this_document_array["hash"]);
        $new_document->setOwner_hidden(1);
        $new_document->setCopy_of($this->getId());
        $new_document->setName($this->getId());
        $new_document->setOriginal_name($this->getOriginalName());
        $new_document->setAccess_key(bin2hex(random_bytes(9)));
        $new_document->setFormat($this_document_array["format"]);
        $new_document->setType($this->getVKAPIType());
        $new_document->setFolder_id(0);
        $new_document->setPreview($this_document_array["preview"]);
        $new_document->setTags($this_document_array["tags"]);
        $new_document->setFilesize($this_document_array["filesize"]);

        $new_document->save();

        return $new_document;
    }

    public function setTags(?string $tags): bool
    {
        if (is_null($tags)) {
            $this->stateChanges("tags", null);
            return true;
        }

        $parsed = explode(",", $tags);
        if (sizeof($parsed) < 1 || $parsed[0] == "") {
            $this->stateChanges("tags", null);
            return true;
        }

        $result = "";
        foreach ($parsed as $tag) {
            $result .= trim($tag) . ($tag != end($parsed) ? "," : '');
        }

        $this->stateChanges("tags", ovk_proc_strtr($result, 500));
        return true;
    }

    public function getOwner(bool $real = false): RowModel
    {
        $oid = (int) $this->getRecord()->owner;
        if ($oid > 0) {
            return (new Users())->get($oid);
        } else {
            return (new Clubs())->get($oid * -1);
        }
    }

    public function getFileExtension(): string
    {
        if ($this->tmp_format) {
            return $this->tmp_format;
        }

        return $this->getRecord()->format;
    }

    public function getPrettyId(): string
    {
        return $this->getVirtualId() . "_" . $this->getId();
    }

    public function getPrettiestId(): string
    {
        return $this->getVirtualId() . "_" . $this->getId() . "_" . $this->getAccessKey();
    }

    public function getOriginal(): Document
    {
        return $this->getRecord()->copy_of;
    }

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getOriginalName(): string
    {
        return $this->getRecord()->original_name;
    }

    public function getVKAPIType(): int
    {
        return $this->getRecord()->type;
    }

    public function getFolder(): int
    {
        return $this->getRecord()->folder_id;
    }

    public function getTags(): array
    {
        $tags = $this->getRecord()->tags;
        if (!$tags) {
            return [];
        }

        return explode(",", $tags ?? "");
    }

    public function getFilesize(): int
    {
        return $this->getRecord()->filesize;
    }

    public function getPreview(): ?RowModel
    {
        $preview_array = $this->getRecord()->preview;
        $preview  = explode(",", $this->getRecord()->preview)[0];
        $model    = null;
        $exploded = explode("_", $preview);

        switch ($exploded[0]) {
            case "photo":
                $model = (new Photos())->get((int) $exploded[1]);
                break;
        }

        return $model;
    }

    public function getOwnerID(): int
    {
        return $this->getRecord()->owner;
    }

    public function toApiPreview(): object
    {
        $preview = $this->getPreview();
        if ($preview instanceof Photo) {
            return (object) [
                "photo" => [
                    "sizes" => array_values($preview->getVkApiSizes()),
                ],
            ];
        }
    }

    public function canBeModifiedBy(User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->getOwnerID() < 0) {
            return (new Clubs())->get(abs($this->getOwnerID()))->canBeModifiedBy($user);
        }

        return $this->getOwnerID() === $user->getId();
    }

    public function toVkApiStruct(?User $user = null, bool $return_tags = false): object
    {
        $res = new \stdClass();
        $res->id = $this->getId();
        $res->owner_id = $this->getVirtualId();
        $res->title = $this->getName();
        $res->size  = $this->getFilesize();
        $res->ext   = $this->getFileExtension();
        $res->url   = $this->getURL();
        $res->date  = $this->getPublicationTime()->timestamp();
        $res->type  = $this->getVKAPIType();
        $res->is_hidden   = (int) $this->isOwnerHidden();
        $res->is_licensed = (int) $this->isLicensed();
        $res->is_unsafe   = (int) $this->isUnsafe();
        $res->folder_id   = (int) $this->getFolder();
        $res->access_key  = $this->getAccessKey();
        $res->private_url = "";
        if ($user) {
            $res->can_manage = $this->canBeModifiedBy($user);
        }

        if ($this->hasPreview()) {
            $res->preview = $this->toApiPreview();
        }

        if ($return_tags) {
            $res->tags = $this->getTags();
        }

        return $res;
    }

    public function delete(bool $softly = true, bool $all_copies = false): void
    {
        if ($all_copies) {
            $ctx = DatabaseConnection::i()->getContext();
            $ctx->table("documents")->where("copy_of", $this->getId())->delete();
        }
        parent::delete($softly);
    }

    public static function detectTypeByFormat(string $format)
    {
        switch (mb_strtolower($format)) {
            case "txt": case "docx": case "doc": case "odt": case "pptx": case "ppt": case "xlsx": case "xls": case "md":
                return 1;
            case "zip": case "rar": case "7z":
                return 2;
            case "gif": case "apng":
                return 3;
            case "jpg": case "jpeg": case "png": case "psd": case "ps": case "webp":
                return 4;
            case "mp3":
                return 5;
            case "mp4": case "avi":
                return 6;
            case "pdf": case "djvu": case "epub": case "fb2":
                return 7;
            default:
                return 8;
        }
    }
}
