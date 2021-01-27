<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Utils\Image;

class Photo extends Media
{
    protected $tableName     = "photos";
    protected $fileExtension = "jpeg";
    
    protected function saveFile(string $filename, string $hash): bool
    {
        $image = Image::fromFile($filename);
        if(($image->height >= ($image->width * pi())) || ($image->width >= ($image->height * pi())))
            throw new ISE("Invalid layout: expected layout that matches (x, ?!>3x)");
        
        $image->save($this->pathFromHash($hash), 92, Image::JPEG);
        
        return true;
    }
    
    function crop(real $left, real $top, real $width, real $height): bool
    {
        if(isset($this->changes["hash"]))
            $hash = $this->changes["hash"];
        else if(!is_null($this->getRecord()))
            $hash = $this->getRecord()->hash;
        else
            throw new ISE("Cannot crop uninitialized image. Please call setFile(\$_FILES[...]) first.");
        
        $image = Image::fromFile($this->pathFromHash($hash));
        $image->crop($left, $top, $width, $height);
        return $image->save($this->pathFromHash($hash));
    }
    
    function isolate(): void
    {
        if(is_null($this->getRecord()))
            throw new ISE("Cannot isolate unpresisted image. Please save() it first.");
        
        DB::i()->getContext()->table("album_relations")->where("media", $this->getRecord()->id)->delete();
    }
}
