<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Repositories\Videos;

class VideoAlbum extends MediaCollection
{
    public const SPECIAL_ADDED    = 16;
    public const SPECIAL_UPLOADED = 32;

    protected $tableName       = "video_playlists";
    protected $relTableName    = "vp_relations";
    protected $entityTableName = "videos";
    protected $entityClassName = 'openvk\Web\Models\Entities\Video';

    protected $specialNames = [
        16 => "_added_album",
        32 => "_uploaded_album",
    ];

    public function getCoverURL(): ?string
    {
        $cover = $this->getCoverVideo();
        if (!$cover) {
            return "/assets/packages/static/openvk/img/camera_200.png";
        }

        return $cover->getThumbnailURL();
    }

    public function getCoverVideo(): ?Photo
    {
        $cover = $this->getRecord()->cover_video;
        if (!$cover) {
            $vids = iterator_to_array($this->fetch(1, 1));
            $vid  = $vids[0] ?? null;
            if (!$vid || $vid->isDeleted()) {
                return null;
            } else {
                return $vid;
            }
        }

        return (new Videos())->get($cover);
    }
}
