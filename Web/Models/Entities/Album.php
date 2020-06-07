<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Photos;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection;

class Album extends RowModel
{
    const SPECIAL_AVATARS = 16;
    const SPECIAL_WALL    = 32;
    
    protected $tableName = "albums";
    
    function getOwner(): RowModel
    {
        $oid = $this->getRecord()->owner;
        if($oid > 0)
            return (new Users)->get($oid);
        else
            return (new Clubs)->get($oid * -1);
    }
    
    function getPrettyId(): string
    {
        return $this->getRecord()->owner . "_" . $this->getRecord()->id;
    }
    
    function getName(): string
    {
        switch($this->getRecord()->special_type) {
            case Album::SPECIAL_AVATARS:
                return "Изображения со страницы";
            case Album::SPECIAL_WALL:
                return "Изображения со стены";
            default:
                return $this->getRecord()->name;
        }
    }
    
    function getDescription(): ?string
    {
        return $this->getRecord()->description;
    }
    
    function getCoverPhoto(): ?Photo
    {
        $cover = $this->getRecord()->cover_photo;
        if(!$cover) {
            $photos = iterator_to_array($this->getPhotos(1, 1));
            $photo  = $photos[0] ?? NULL;
            if(!$photo || $photo->isDeleted())
                return NULL;
            else
                return $photo;
        }
        
        return (new Photos)->get($cover);
    }
    
    function getPhotos(int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        
        foreach($this->getRecord()->related("album_relations.album")->page($page, $perPage)->order("photo ASC") as $rel) {
            $photo = $rel->ref("photos", "photo");
            if(!$photo) continue;
            
            yield new Photo($photo);
        }
    }
    
    function getPhotosCount(): int
    {
        return sizeof($this->getRecord()->related("album_relations.album"));
    }
    
    function getCreationTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
    
    function getPublicationTime(): DateTime
    {
        return $this->getCreationTime();
    }
    
    function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        if(is_null($edited)) return NULL;
        
        return new DateTime($edited);
    }
    
    function isCreatedBySystem(): bool
    {
        return $this->getRecord()->special_type !== 0;
    }
    
    function addPhoto(Photo $photo): void
    {
        DatabaseConnection::i()->getContext()->table("album_relations")->insert([
            "album" => $this->getRecord()->id,
            "photo" => $photo->getId(),
        ]);
    }
    
    function removePhoto(Photo $photo): void
    {
        DatabaseConnection::i()->getContext()->table("album_relations")->where([
            "album" => $this->getRecord()->id,
            "photo" => $photo->getId(),
        ])->delete();
    }
    
    function hasPhoto(Photo $photo): bool
    {
        $rel = DatabaseConnection::i()->getContext()->table("album_relations")->where([
            "album" => $this->getRecord()->id,
            "photo" => $photo->getId(),
        ])->fetch();
        
        return !is_null($rel);
    }
    
    use Traits\TOwnable;
}
