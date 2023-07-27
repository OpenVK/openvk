<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Photos;

class Album extends MediaCollection
{
    const SPECIAL_AVATARS = 16;
    const SPECIAL_WALL    = 32;
    
    protected $tableName       = "albums";
    protected $relTableName    = "album_relations";
    protected $entityTableName = "photos";
    protected $entityClassName = 'openvk\Web\Models\Entities\Photo';
    
    protected $specialNames = [
        16 => "_avatar_album",
        32 => "_wall_album",
        64 => "_saved_photos_album",
    ];
    
    function getCoverURL(): ?string
    {
        $coverPhoto = $this->getCoverPhoto();
        if(!$coverPhoto)
            return "/assets/packages/static/openvk/img/camera_200.png";
        
        return $coverPhoto->getURL();
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
        return $this->fetch($page, $perPage);
    }
    
    function getPhotosCount(): int
    {
        return $this->size();
    }
    
    function addPhoto(Photo $photo): void
    {
        $this->add($photo);
    }
    
    function removePhoto(Photo $photo): void
    {
        $this->remove($photo);
    }
    
    function hasPhoto(Photo $photo): bool
    {
        return $this->has($photo);
    }
}
