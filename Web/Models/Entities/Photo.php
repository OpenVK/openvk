<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
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

    protected function saveFile(string $filename, string $hash): bool
    {
        $image = Image::fromFile($filename);
        if(($image->height >= ($image->width * Photo::ALLOWED_SIDE_MULTIPLIER)) || ($image->width >= ($image->height * Photo::ALLOWED_SIDE_MULTIPLIER)))
            throw new ISE("Invalid layout: image is too wide/short");
        
        $image->save($this->pathFromHash($hash), 92, Image::JPEG);
        
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

	function getDimensions(): array
	{
		$hash = $this->getRecord()->hash;

		return array_slice(getimagesize($this->pathFromHash($hash)), 0, 2);
	}

    function getDimentions(): array
    {
        trigger_error("getDimentions is deprecated, use Photo::getDimensions instead.");

        return $this->getDimensions();
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

        $res->src =
            $res->src_small = $res->src_big = $res->src_xbig = $res->src_xxbig =
            $res->src_xxxbig = $res->photo_75 = $res->photo_130 = $res->photo_604 =
            $res->photo_807 = $res->photo_1280 = $res->photo_2560 = $this->getURL();

        return $res;
    }

    static function fastMake(int $owner, array $file, string $description = "", ?Album $album = NULL, bool $anon = false): Photo
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
