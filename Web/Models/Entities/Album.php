<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Repositories\Photos;

class Album extends MediaCollection
{
    public const SPECIAL_AVATARS = 16;
    public const SPECIAL_WALL    = 32;

    protected $tableName       = "albums";
    protected $relTableName    = "album_relations";
    protected $entityTableName = "photos";
    protected $entityClassName = 'openvk\Web\Models\Entities\Photo';

    protected $specialNames = [
        16 => "_avatar_album",
        32 => "_wall_album",
        64 => "_saved_photos_album",
    ];

    public function getCoverURL(): ?string
    {
        $coverPhoto = $this->getCoverPhoto();
        if (!$coverPhoto) {
            $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

            return $server_url . "/assets/packages/static/openvk/img/camera_200.png";
        }

        return $coverPhoto->getURL();
    }

    public function getCoverPhoto(): ?Photo
    {
        $cover = $this->getRecord()->cover_photo;
        if (!$cover) {
            $photos = iterator_to_array($this->getPhotos(1, 1));
            $photo  = $photos[0] ?? null;
            if (!$photo || $photo->isDeleted()) {
                return null;
            } else {
                return $photo;
            }
        }

        return (new Photos())->get($cover);
    }

    public function getPhotos(int $page = 1, ?int $perPage = null): \Traversable
    {
        return $this->fetch($page, $perPage);
    }

    public function getPhotosCount(): int
    {
        return $this->size();
    }

    public function addPhoto(Photo $photo): void
    {
        $this->add($photo);
    }

    public function removePhoto(Photo $photo): void
    {
        $this->remove($photo);
    }

    public function hasPhoto(Photo $photo): bool
    {
        return $this->has($photo);
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        $owner = $this->getOwner();

        if (get_class($owner) == "openvk\\Web\\Models\\Entities\\User") {
            return $owner->canBeViewedBy($user) && $owner->getPrivacyPermission('photos.read', $user);
        } else {
            return $owner->canBeViewedBy($user);
        }
    }

    public function toVkApiStruct(?User $user = null, bool $need_covers = false, bool $photo_sizes = false): object
    {
        $res = (object) [];

        $res->id              = $this->getId();
        $res->thumb_id        = !is_null($this->getCoverPhoto()) ? $this->getCoverPhoto()->getPrettyId() : 0;
        $res->owner_id        = $this->getOwner()->getRealId();
        $res->title           = $this->getName();
        $res->description     = $this->getDescription();
        $res->created         = $this->getCreationTime()->timestamp();
        $res->updated         = $this->getEditTime() ? $this->getEditTime()->timestamp() : $res->created;
        $res->size            = $this->size();
        $res->privacy_comment = 1;
        $res->upload_by_admins_only = 1;
        $res->comments_disabled = 0;
        $res->can_upload      = $this->canBeModifiedBy($user); # thisUser недоступен в entities
        if ($need_covers) {
            $res->thumb_src   = $this->getCoverURL();

            if ($photo_sizes) {
                $res->sizes   = !is_null($this->getCoverPhoto()) ? $this->getCoverPhoto()->getVkApiSizes() : null;
            }
        }

        return $res;
    }
}
