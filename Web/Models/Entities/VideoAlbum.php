<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Videos;

class VideoAlbum extends MediaCollection
{
    const SPECIAL_ADDED    = 16;
    const SPECIAL_UPLOADED = 32;
    
    protected $tableName       = "video_playlists";
    protected $relTableName    = "vp_relations";
    protected $entityTableName = "videos";
    protected $entityClassName = 'openvk\Web\Models\Entities\Video';
    
    protected $specialNames = [
        16 => "_added_album",
        32 => "_uploaded_album",
    ];
    
    function getCoverURL(): ?string
    {
        $cover = $this->getCoverVideo();
        if(!$cover)
            return "/assets/packages/static/openvk/img/camera_200.png";
        
        return $cover->getThumbnailURL();
    }
    
    function getCoverVideo(): ?Photo
    {
        $cover = $this->getRecord()->cover_video;
        if(!$cover) {
            $vids = iterator_to_array($this->fetch(1, 1));
            $vid  = $vids[0] ?? NULL;
            if(!$vid || $vid->isDeleted())
                return NULL;
            else
                return $vid;
        }
        
        return (new Videos)->get($cover);
    }
}
