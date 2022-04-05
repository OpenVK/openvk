<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use MessagePack\MessagePack;
use openvk\Web\Models\Entities\Album;
use openvk\Web\Models\Repositories\Albums;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Utils\Image;

class Photo extends Media
{
    protected $tableName     = "photos";
    protected $fileExtension = "jpeg";

    const ALLOWED_SIDE_MULTIPLIER = 7;

    private function resizeImage(string $filename, string $outputDir, \SimpleXMLElement $size): array
    {
        $res   = [false];
        $image = Image::fromFile($filename);
        $requiresProportion = ((string) $size["requireProp"]) != "none";
        if($requiresProportion) {
            $props = explode(":", (string) $size["requireProp"]);
            $px = (int) $props[0];
            $py = (int) $props[1];
            if(($image->getWidth() / $image->getHeight()) > ($px / $py)) {
                # For some weird reason using resize with EXACT flag causes system to consume an unholy amount of RAM
                $image->crop(0, 0, "100%", (int) ceil(($px * $image->getWidth()) / $py));
                $res[0] = true;
            }
        }

        if(isset($size["maxSize"])) {
            $maxSize = (int) $size["maxSize"];
            $image->resize($maxSize, $maxSize, Image::SHRINK_ONLY | Image::FIT);
        } else if(isset($size["maxResolution"])) {
            $resolution = explode("x", (string) $size["maxResolution"]);
            $image->resize((int) $resolution[0], (int) $resolution[1], Image::SHRINK_ONLY | Image::FIT);
        } else {
            throw new \RuntimeException("Malformed size description: " . (string) $size["id"]);
        }

        $res[1] = $image->getWidth();
        $res[2] = $image->getHeight();
        if($res[1] <= 300 || $res[2] <= 300)
            $image->save("$outputDir/" . (string) $size["id"] . ".gif");
        else
            $image->save("$outputDir/" . (string) $size["id"] . ".jpeg");

        imagedestroy($image->getImageResource());
        unset($image);

        return $res;
    }

    private function saveImageResizedCopies(string $filename, string $hash): void
    {
        $dir = dirname($this->pathFromHash($hash));
        $dir = "$dir/$hash" . "_cropped";
        if(!is_dir($dir)) {
            @unlink($dir); # Added to transparently bypass issues with dead pesudofolders summoned by buggy SWIFT impls (selectel)
            mkdir($dir);
        }

        $sizes = simplexml_load_file(OPENVK_ROOT . "/data/photosizes.xml");
        if(!$sizes)
            throw new \RuntimeException("Could not load photosizes.xml!");

        $sizesMeta = [];
        foreach($sizes->Size as $size)
            $sizesMeta[(string) $size["id"]] = $this->resizeImage($filename, $dir, $size);

        $sizesMeta = MessagePack::pack($sizesMeta);
        $this->stateChanges("sizes", $sizesMeta);
    }

    protected function saveFile(string $filename, string $hash): bool
    {
        $image = Image::fromFile($filename);
        if(($image->height >= ($image->width * Photo::ALLOWED_SIDE_MULTIPLIER)) || ($image->width >= ($image->height * Photo::ALLOWED_SIDE_MULTIPLIER)))
            throw new ISE("Invalid layout: image is too wide/short");

        $image->resize(8192, 4320, Image::SHRINK_ONLY | Image::FIT);
        $image->save($this->pathFromHash($hash), 92, Image::JPEG);
        $this->saveImageResizedCopies($filename, $hash);
        
        return true;
    }
    
    function crop(real $left, real $top, real $width, real $height): void
    {
        if(isset($this->changes["hash"]))
            $hash = $this->changes["hash"];
        else if(!is_null($this->getRecord()))
            $hash = $this->getRecord()->hash;
        else
            throw new ISE("Cannot crop uninitialized image. Please call setFile(\$_FILES[...]) first.");
        
        $image = Image::fromFile($this->pathFromHash($hash));
        $image->crop($left, $top, $width, $height);
        $image->save($this->pathFromHash($hash));
    }
    
    function isolate(): void
    {
        if(is_null($this->getRecord()))
            throw new ISE("Cannot isolate unpresisted image. Please save() it first.");
        
        DB::i()->getContext()->table("album_relations")->where("media", $this->getRecord()->id)->delete();
    }

    function getSizes(bool $upgrade = false, bool $forceUpdate = false): ?array
    {
        $sizes = $this->getRecord()->sizes;
        if(!$sizes || $forceUpdate) {
            if($forceUpdate || $upgrade || OPENVK_ROOT_CONF["openvk"]["preferences"]["photos"]["upgradeStructure"]) {
                $hash  = $this->getRecord()->hash;
                $this->saveImageResizedCopies($this->pathFromHash($hash), $hash);
                $this->save();

                return $this->getSizes();
            }

            return NULL;
        }

        $res   = [];
        $sizes = MessagePack::unpack($sizes);
        foreach($sizes as $id => $meta) {
            $url  = $this->getURL();
            $url  = str_replace(".$this->fileExtension", "_cropped/$id.", $url);
            $url .= ($meta[1] <= 300 || $meta[2] <= 300) ? "gif" : "jpeg";

            $res[$id] = (object) [
                "url"    => $url,
                "width"  => $meta[1],
                "height" => $meta[2],
                "crop"   => $meta[0]
            ];
        }

        [$x, $y] = $this->getDimensions();
        $res["UPLOADED_MAXRES"] = (object) [
            "url"    => $this->getURL(),
            "width"  => $x,
            "height" => $y,
            "crop"   => false
        ];

        return $res;
    }

    function getVkApiSizes(): ?array
    {
        $res   = [];
        $sizes = $this->getSizes();
        if(!$sizes)
            return NULL;

        $manifest = simplexml_load_file(OPENVK_ROOT . "/data/photosizes.xml");
        if(!$manifest)
            return NULL;

        $mappings = [];
        foreach($manifest->Size as $size)
            $mappings[(string) $size["id"]] = (string) $size["vkId"];

        foreach($sizes as $id => $meta)
            $res[$mappings[$id] ?? $id] = $meta;

        return $res;
    }

    function getURLBySizeId(string $size): string
    {
        $sizes = $this->getSizes();
        if(!$sizes)
            return $this->getURL();

        $size = $sizes[$size];
        if(!$size)
            return $this->getURL();

        return $size->url;
    }

    function getDimensions(): array
    {
        $x = $this->getRecord()->width;
        $y = $this->getRecord()->height;
        if(!$x) { # no sizes in database
            $hash  = $this->getRecord()->hash;
            $image = Image::fromFile($this->pathFromHash($hash));

            $x = $image->getWidth();
            $y = $image->getHeight();
            $this->stateChanges("width", $x);
            $this->stateChanges("height", $y);
            $this->save();
        }

        return [$x, $y];
    }

    function getAlbum(): ?Album
    {
        return (new Albums)->getAlbumByPhotoId($this);
    }

    function toVkApiStruct(): object
    {
        $res = (object) [];

        $res->id       = $res->pid = $this->getId();
        $res->owner_id = $res->user_id = $this->getOwner()->getId()->getId();
        $res->aid      = $res->album_id = NULL;
        $res->width    = $this->getDimensions()[0];
        $res->height   = $this->getDimensions()[1];
        $res->date     = $res->created = $this->getPublicationTime()->timestamp();

        $res->sizes        = $this->getVkApiSizes();
        $res->src_small    = $res->photo_75 = $this->getURLBySizeId("miniscule");
        $res->src          = $res->photo_130 = $this->getURLBySizeId("tiny");
        $res->src_big      = $res->photo_604 = $this->getURLBySizeId("normal");
        $res->src_xbig     = $res->photo_807 = $this->getURLBySizeId("large");
        $res->src_xxbig    = $res->photo_1280 = $this->getURLBySizeId("larger");
        $res->src_xxxbig   = $res->photo_2560 = $this->getURLBySizeId("original");
        $res->src_original = $res->url = $this->getURLBySizeId("UPLOADED_MAXRES");

        return $res;
    }

    static function fastMake(int $owner, string $description = "", array $file, ?Album $album = NULL, bool $anon = false): Photo
    {
        $photo = new static;
        $photo->setOwner($owner);
        $photo->setDescription(iconv_substr($description, 0, 36) . "...");
        $photo->setAnonymous($anon);
        $photo->setCreated(time());
        $photo->setFile($file);
        $photo->save();

        if(!is_null($album))
            $album->addPhoto($photo);

        return $photo;
    }
}
