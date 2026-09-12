<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use MessagePack\MessagePack;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;
use openvk\Web\Models\Entities\Album;
use openvk\Web\Models\Repositories\Albums;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Utils\Image;

class Photo extends Media
{
    protected $tableName     = "photos";
    public $shortName        = "photo";
    protected $fileExtension = "jpeg";
    protected $containsContextColumns = true;

    public const ALLOWED_SIDE_MULTIPLIER = 7;

    /**
     * @throws \ImagickException
     * @throws ImageException
     * @throws UnknownImageFileException
     */
    private function resizeImage(\Imagick $image, string $outputDir, \SimpleXMLElement $size): array
    {
        $res = [false];
        $requiresProportion = ((string) $size["requireProp"]) != "none";
        if ($requiresProportion) {
            $props = explode(":", (string) $size["requireProp"]);
            $px = (int) $props[0];
            $py = (int) $props[1];
            if (($image->getImageWidth() / $image->getImageHeight()) > ($px / $py)) {
                $height = (int) ceil(($px * $image->getImageWidth()) / $py);
                $image->cropImage($image->getImageWidth(), $height, 0, 0);
                $res[0] = true;
            }
        }

        if (isset($size["maxSize"])) {
            $maxSize = (int) $size["maxSize"];
            $sizes   = Image::calculateSize($image->getImageWidth(), $image->getImageHeight(), $maxSize, $maxSize, Image::SHRINK_ONLY | Image::FIT);
            $image->resizeImage($sizes[0], $sizes[1], \Imagick::FILTER_HERMITE, 1);
        } elseif (isset($size["maxResolution"])) {
            $resolution = explode("x", (string) $size["maxResolution"]);
            $sizes = Image::calculateSize(
                $image->getImageWidth(),
                $image->getImageHeight(),
                (int) $resolution[0],
                (int) $resolution[1],
                Image::SHRINK_ONLY | Image::FIT
            );
            $image->resizeImage($sizes[0], $sizes[1], \Imagick::FILTER_HERMITE, 1);
        } else {
            throw new \RuntimeException("Malformed size description: " . (string) $size["id"]);
        }

        $res[1] = $image->getImageWidth();
        $res[2] = $image->getImageHeight();
        if ($res[1] <= 300 || $res[2] <= 300) {
            $image->writeImage("$outputDir/$size[id].gif");
        } else {
            $image->writeImage("$outputDir/$size[id].jpeg");
        }

        $res[3] = true;
        $image->destroy();
        unset($image);

        return $res;
    }

    private function saveImageResizedCopies(?\Imagick $image, string $filename, string $hash): void
    {
        if (!$image) {
            $image = new \Imagick();
            $image->readImage($filename);
        }

        $dir = dirname($this->pathFromHash($hash));
        $dir = "$dir/$hash" . "_cropped";
        if (!is_dir($dir)) {
            @unlink($dir); # Added to transparently bypass issues with dead pesudofolders summoned by buggy SWIFT impls (selectel)
            mkdir($dir);
        }

        $sizes = simplexml_load_file(OPENVK_ROOT . "/data/photosizes.xml");
        if (!$sizes) {
            throw new \RuntimeException("Could not load photosizes.xml!");
        }

        $sizesMeta = [];
        if (OPENVK_ROOT_CONF["openvk"]["preferences"]["photos"]["photoSaving"] === "quick") {
            foreach ($sizes->Size as $size) {
                $sizesMeta[(string) $size["id"]] = [false, false, false, false];
            }
        } else {
            foreach ($sizes->Size as $size) {
                $sizesMeta[(string) $size["id"]] = $this->resizeImage(clone $image, $dir, $size);
            }
        }

        $sizesMeta = MessagePack::pack($sizesMeta);
        $this->stateChanges("sizes", $sizesMeta);
    }

    protected function saveFile(string $filename, string $hash): bool
    {
        $input_image = new \Imagick();
        $input_image->readImage($filename);
        $h = $input_image->getImageHeight();
        $w = $input_image->getImageWidth();
        if (($h >= ($w * Photo::ALLOWED_SIDE_MULTIPLIER)) || ($w >= ($h * Photo::ALLOWED_SIDE_MULTIPLIER))) {
            throw new ISE("Invalid layout: image is too wide/short");
        }

        # gif fix 10.01.2025
        if ($input_image->getImageFormat() === 'GIF') {
            $input_image->setIteratorIndex(0);
        }

        # png workaround (transparency to white)
        $image = new \Imagick();
        $bg = new \ImagickPixel('white');
        $image->newImage($w, $h, $bg);
        $image->compositeImage($input_image, \Imagick::COMPOSITE_OVER, 0, 0);

        $sizes = Image::calculateSize(
            $image->getImageWidth(),
            $image->getImageHeight(),
            8192,
            4320,
            Image::SHRINK_ONLY | Image::FIT
        );

        $image->resizeImage($sizes[0], $sizes[1], \Imagick::FILTER_HERMITE, 1);
        $image->writeImage($this->pathFromHash($hash));
        $this->saveImageResizedCopies($image, $filename, $hash);

        return true;
    }

    public function crop(float $left, float $top, float $width, float $height): void
    {
        if (isset($this->changes["hash"])) {
            $hash = $this->changes["hash"];
        } elseif (!is_null($this->getRecord())) {
            $hash = $this->getRecord()->hash;
        } else {
            throw new ISE("Cannot crop uninitialized image. Please call setFile(\$_FILES[...]) first.");
        }

        $image = Image::fromFile($this->pathFromHash($hash));
        $image->crop($left, $top, $width, $height);
        $image->save($this->pathFromHash($hash));
    }

    public function isolate(): void
    {
        if (is_null($this->getRecord())) {
            throw new ISE("Cannot isolate unpresisted image. Please save() it first.");
        }

        DB::i()->getContext()->table("album_relations")->where("media", $this->getRecord()->id)->delete();
    }

    public function getSizes(bool $upgrade = false, bool $forceUpdate = false): ?array
    {
        $sizes = $this->getRecord()->sizes;
        if (!$sizes || $forceUpdate) {
            if ($forceUpdate || $upgrade || OPENVK_ROOT_CONF["openvk"]["preferences"]["photos"]["upgradeStructure"]) {
                $hash = $this->getRecord()->hash;
                $this->saveImageResizedCopies(null, $this->pathFromHash($hash), $hash);
                $this->save();

                return $this->getSizes();
            }

            return null;
        }

        $res   = [];
        $sizes = MessagePack::unpack($sizes);
        $keySuffix = ($this->getAccessKey() != null) ? "?key=" . $this->getAccessKey() : "";

        foreach ($sizes as $id => $meta) {
            if (isset($meta[3]) && !$meta[3]) {
                $url = ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/photos/thumbnails/" . $this->getId() . "_$id.jpeg" . $keySuffix;
                $photoobj = [
                    "url"    => $url,
                    "src"    => $url,
                    "width"  => 0,
                    "height" => 0,
                    "crop"   => false,
                ];

                if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && VKAPI_DECL_VER_MINOR < 77) {
                    $photoobj['src'] = $url;
                }

                $res[$id] = (object) $photoobj;

                continue;
            }

            $url  = $this->getURL();
            $url  = str_replace(".$this->fileExtension", "_cropped/$id.", $url);
            $url .= ($meta[1] <= 300 || $meta[2] <= 300) ? "gif" : "jpeg";
            $url .= $keySuffix;

            $photoobj = [
                "url"    => $url,
                "src"    => $url,
                "width"  => (int) ($meta[1] ?? 0),
                "height" => (int) ($meta[2] ?? 0),
                "crop"   => $meta[0],
            ];

            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && VKAPI_DECL_VER_MINOR < 77) {
                $photoobj['src'] = $url;
            }

            $res[$id] = (object) $photoobj;
        }

        [$x, $y] = $this->getDimensions();
        $uploadedUrl = $this->getURL() . $keySuffix;
        $photoobj = [
            "url"    => $uploadedUrl,
            "src"    => $uploadedUrl,
            "width"  => (int) ($x ?? 0),
            "height" => (int) ($y ?? 0),
            "crop"   => false,
        ];

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && VKAPI_DECL_VER_MINOR < 77) {
            $photoobj['src'] = $uploadedUrl;
        }

        $res["UPLOADED_MAXRES"] = (object) $photoobj;

        return $res;
    }

    public static function normalizeSizeName(string|int $size): string
    {
        $size = strtolower(trim((string) $size));
        $map = [
            // VK letter codes
            "s" => "miniscule",
            "m" => "tiny",
            "o" => "tinier",
            "p" => "xsmall",
            "q" => "small",
            "r" => "medium",
            "x" => "normal",
            "y" => "large",
            "z" => "larger",
            "w" => "original",
            // Dimensions
            "75" => "miniscule",
            "130" => "tiny",
            "200" => "xsmall",
            "320" => "small",
            "510" => "medium",
            "604" => "normal",
            "807" => "large",
            "1080" => "larger",
            "1280" => "larger",
            "2560" => "original",
            // photo_XXX
            "photo_75" => "miniscule",
            "photo_130" => "tiny",
            "photo_604" => "normal",
            "photo_807" => "large",
            "photo_1280" => "larger",
            "photo_2560" => "original",
            // src_XXX
            "src_small" => "miniscule",
            "src" => "tiny",
            "src_big" => "normal",
            "src_xbig" => "large",
            "src_xxbig" => "larger",
            "src_xxxbig" => "original",
            "src_original" => "UPLOADED_MAXRES",
            // Generic words
            "thumb" => "small",
            "preview" => "medium",
            "full" => "original",
            "max" => "UPLOADED_MAXRES",
            "orig" => "original",
            "raw" => "UPLOADED_MAXRES",
        ];

        return $map[$size] ?? $size;
    }

    public function forceSize(string|int $sizeName): bool
    {
        $sizeName = self::normalizeSizeName($sizeName);
        $hash     = $this->getRecord()->hash;
        $sizes    = MessagePack::unpack($this->getRecord()->sizes);
        $size     = $sizes[$sizeName] ?? false;
        if (!$size) {
            return $size;
        }

        if (!isset($size[3]) || $size[3] === true) {
            return true;
        }

        $path = $this->pathFromHash($hash);
        $dir  = dirname($this->pathFromHash($hash));
        $dir  = "$dir/$hash" . "_cropped";
        if (!is_dir($dir)) {
            @unlink($dir);
            mkdir($dir);
        }

        $sizeMetas = simplexml_load_file(OPENVK_ROOT . "/data/photosizes.xml");
        if (!$sizeMetas) {
            throw new \RuntimeException("Could not load photosizes.xml!");
        }

        $sizeInfo = null;
        foreach ($sizeMetas->Size as $s) {
            if ((string) $s["id"] === $sizeName || (string) $s["vkId"] === $sizeName) {
                $sizeInfo = $s;
                break;
            }
        }

        if (!$sizeInfo) {
            return false;
        }

        $pic = new \Imagick();
        $pic->readImage($path);
        $sizes[$sizeName] = $this->resizeImage($pic, $dir, $sizeInfo);

        $this->stateChanges("sizes", MessagePack::pack($sizes));
        $this->save();

        return (bool) ($sizes[$sizeName][3] ?? true);
    }

    public function getVkApiSizes(): ?array
    {
        $res   = [];
        $sizes = $this->getSizes();
        if (!$sizes) {
            return null;
        }

        $manifest = simplexml_load_file(OPENVK_ROOT . "/data/photosizes.xml");
        if (!$manifest) {
            return null;
        }

        $mappings = [];
        foreach ($manifest->Size as $size) {
            $mappings[(string) $size["id"]] = (string) $size["vkId"];
        }

        foreach ($sizes as $id => $meta) {
            $type       = $mappings[$id] ?? $id;
            $meta->type = $type;
            if (!isset($meta->url) && isset($meta->src)) {
                $meta->url = $meta->src;
            }
            if (!isset($meta->src) && isset($meta->url)) {
                $meta->src = $meta->url;
            }
            $res[$type] = $meta;
        }

        return $res;
    }

    public function getURLBySizeId(string|int $size): string
    {
        $normSize = self::normalizeSizeName($size);
        $sizes    = $this->getSizes();
        if (!$sizes) {
            return $this->getURL();
        }

        $sizeObj = $sizes[$normSize] ?? $sizes[(string) $size] ?? null;
        if (!$sizeObj) {
            return $this->getURL();
        }

        $url = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && VKAPI_DECL_VER_MINOR < 77)
            ? ($sizeObj->src ?? $sizeObj->url ?? $this->getURL())
            : ($sizeObj->url ?? $sizeObj->src ?? $this->getURL());
        if ($this->getAccessKey() != null && !str_contains($url, "key=")) {
            return $url . (str_contains($url, "?") ? "&key=" : "?key=") . $this->getAccessKey();
        }

        return $url;
    }

    public function getFilePathBySizeId(string|int $size): ?string
    {
        $normSize = self::normalizeSizeName($size);
        $hash     = $this->getRecord()->hash;
        $origPath = $this->pathFromHash($hash);

        if ($normSize === "UPLOADED_MAXRES") {
            return file_exists($origPath) ? $origPath : null;
        }

        $dir = dirname($origPath) . "/{$hash}_cropped";
        $candidates = [
            "$dir/$normSize.jpeg",
            "$dir/$normSize.jpg",
            "$dir/$normSize.gif",
            "$dir/$normSize.png",
            "$dir/$normSize.webp",
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        try {
            $this->forceSize($normSize);
            foreach ($candidates as $cand) {
                if (file_exists($cand)) {
                    return $cand;
                }
            }
        } catch (\Throwable $e) {
        }

        return file_exists($origPath) ? $origPath : null;
    }

    public function getDimensions(): array
    {
        $x = $this->getRecord()->width;
        $y = $this->getRecord()->height;
        if (!$x) { # no sizes in database
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

    public function getPageURL(): string
    {
        if ($this->isAnonymous()) {
            return "/photos/" . base_convert((string) $this->getId(), 10, 32);
        }

        return "/photo" . $this->getPrettyId();
    }

    public function getAlbum(): ?Album
    {
        $album = (new Albums())->getAlbumByPhotoId($this);
        if (!$album || $album->isDeleted()) {
            return null;
        }

        return $album;
    }

    public function toVkApiStruct(bool $photo_sizes = true, bool $extended = false): object
    {
        $res = (object) [];

        $album = $this->getAlbum();

        $res->id       = $res->pid = (int) $this->getVirtualId();
        $res->owner_id = $res->user_id = (int) $this->getOwner()->getId();
        $res->aid      = $res->album_id = (int) ($album ? $album->getId() : ($this->isUnlisted() ? -3 : 0));
        $dims = $this->getDimensions();
        $res->width    = (int) ($dims[0] ?? 0);
        $res->height   = (int) ($dims[1] ?? 0);
        $res->date     = $res->created = (int) $this->getPublicationTime()->timestamp();
        $res->text     = (string) ($this->getDescription() ?? "");
        $res->access_key = (string) ($this->getAccessKey() ?? "");

        $res->src_small    = $res->photo_75 = $this->getURLBySizeId("miniscule");
        $res->src          = $res->photo_130 = $this->getURLBySizeId("tiny");
        $res->src_big      = $res->photo_604 = $this->getURLBySizeId("normal");
        $res->src_xbig     = $res->photo_807 = $this->getURLBySizeId("large");
        $res->src_xxbig    = $res->photo_1280 = $this->getURLBySizeId("larger");
        $res->src_xxxbig   = $res->photo_2560 = $this->getURLBySizeId("original");
        $res->src_original = $res->url = $this->getURLBySizeId("UPLOADED_MAXRES");
        $res->orig_photo   = (object) [
            "height" => (int) ($res->height ?? 0),
            "width"  => (int) ($res->width ?? 0),
            "type"   => "base",
            "url"    => $this->getURL(),
        ];

        if ($photo_sizes) {
            $vkSizes = $this->getVkApiSizes();
            if (empty($vkSizes)) {
                $w = $res->width ?: 604;
                $h = $res->height ?: 604;
                $vkSizes = [
                    'm' => (object) [
                        'src'    => $res->src ?: $this->getURL(),
                        'url'    => $res->src ?: $this->getURL(),
                        'width'  => (int) min(130, $w),
                        'height' => (int) min(130, $h),
                        'type'   => 'm',
                    ],
                    'x' => (object) [
                        'src'    => $res->src_big ?: $this->getURL(),
                        'url'    => $res->src_big ?: $this->getURL(),
                        'width'  => (int) $w,
                        'height' => (int) $h,
                        'type'   => 'x',
                    ],
                ];
            } else {
                foreach ($vkSizes as &$sz) {
                    if (is_object($sz)) {
                        if (!isset($sz->url) && isset($sz->src)) {
                            $sz->url = $sz->src;
                        }
                        if (!isset($sz->src) && isset($sz->url)) {
                            $sz->src = $sz->url;
                        }
                    }
                }
            }
            $res->sizes = array_values($vkSizes);
        }

        if ($extended || (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5)) {
            $res->likes       = (object) [
                "count"       => (int) $this->getLikesCount(),
                "user_likes"  => 0,
                "can_like"    => 1,
                "can_publish" => 1,
            ];
            $res->comments    = (object) [
                "count"    => (int) $this->getCommentsCount(),
                "can_post" => 1,
            ];
            $res->can_comment = 1;
            $res->can_repost  = 1;
        }

        return $res;
    }

    public function isSystem(): bool
    {
        return (bool) $this->getRecord()->private;
    }

    public function isPrivate(): bool
    {
        return (bool) $this->getRecord()->private || (bool) $this->getRecord()->unlisted;
    }

    public function toApiAttachment(?User $user = null): array
    {
        return [
            "type"  => "photo",
            "photo" => $this->toVkApiStruct(true, false),
        ];
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted() || $this->getOwner()->isDeleted()) {
            return false;
        }

        if ($this->isSystem() || $this->isUnlisted()) {
            if ($user && $user->getId() === $this->getOwner()->getId()) {
                return true;
            }
        }

        if (!is_null($this->getAlbum())) {
            return $this->getAlbum()->canBeViewedBy($user);
        } else {
            return $this->getOwner()->canBeViewedBy($user);
        }
    }

    public static function fastMake(int $owner, string $description, array $file, ?Album $album = null, bool $anon = false): Photo
    {
        $photo = new Photo();
        $photo->setOwner($owner);
        $photo->setDescription(iconv_substr($description, 0, 36) . "...");
        $photo->setAnonymous($anon);
        $photo->setCreated(time());
        $photo->setFile($file);
        $photo->save();

        if (!is_null($album)) {
            $album->addPhoto($photo);
            $album->setEdited(time());
            $album->save();
        }

        return $photo;
    }

    public function setAsFromMessage(): void
    {
        $this->stateChanges("private", 1);
        $this->stateChanges("unlisted", 1);
    }

    public function toNotifApiStruct()
    {
        $res = (object) [];

        $res->id        = $this->getVirtualId();
        $res->owner_id  = $this->getOwner()->getId();
        $res->aid       = 0;
        $res->src       = $this->getURLBySizeId("tiny");
        $res->src_big   = $this->getURLBySizeId("normal");
        $res->src_small = $this->getURLBySizeId("miniscule");
        $res->text      = $this->getDescription();
        $res->created   = $this->getPublicationTime()->timestamp();

        return $res;
    }

    public function delete(bool $softly = true): void
    {
        $album = $this->getAlbum();

        if ($album !== null) {
            $album->removePhoto($this);
        }

        parent::delete($softly);
    }
}
