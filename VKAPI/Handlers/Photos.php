<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Nette\InvalidStateException;
use Nette\Utils\ImageException;
use openvk\Web\Models\Entities\{Photo, Album, Comment};
use openvk\Web\Models\Repositories\Albums;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;

final class Photos extends VKAPIRequestHandler
{
    private function getPhotoUploadUrl(string $field, int $group = 0, bool $multifile = false): string
    {
        $secret     = CHANDLER_ROOT_CONF["security"]["secret"];
        $uploadInfo = [
            1,
            $field,
            (int) $multifile,
            0,
            time(),
            $this->getUser()->getId(),
            $group,
            0, # this is unused but stays here base64 reasons (X2 doesn't work, so there's dummy value for short)
        ];
        $uploadInfo = pack("vZ10v2P3S", ...$uploadInfo);
        $uploadInfo = base64_encode($uploadInfo);
        $uploadHash = hash_hmac("sha3-224", $uploadInfo, $secret);
        $uploadInfo = rawurlencode($uploadInfo);

        return ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/upload/photo/$uploadHash?$uploadInfo";
    }

    private function getImagePath(string $photo, string $hash, ?string& $up = null, ?string& $group = null): string
    {
        $secret = CHANDLER_ROOT_CONF["security"]["secret"];
        if (!hash_equals(hash_hmac("sha3-224", $photo, $secret), $hash)) {
            $this->fail(121, "Incorrect hash");
        }

        [$up, $image, $group] = explode("|", $photo);

        $imagePath = __DIR__ . "/../../tmp/api-storage/photos/$up" . "_$image.oct";
        if (!file_exists($imagePath)) {
            $this->fail(10, "Invalid image");
        }

        return $imagePath;
    }

    public function getOwnerPhotoUploadServer(int $owner_id = 0): object
    {
        $this->requireUser();

        if ($owner_id < 0) {
            $club = (new Clubs())->get(abs($owner_id));
            if (!$club) {
                $this->fail(0o404, "Club not found");
            } elseif (!$club->canBeModifiedBy($this->getUser())) {
                $this->fail(200, "Access: Club can't be 'written' by user");
            }
        }

        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", !isset($club) ? 0 : $club->getId()),
        ];
    }

    public function saveOwnerPhoto(string $photo, string $hash): object
    {
        $imagePath = $this->getImagePath($photo, $hash, $uploader, $group);
        if ($group == 0) {
            $user  = (new \openvk\Web\Models\Repositories\Users())->get((int) $uploader);
            $album = (new Albums())->getUserAvatarAlbum($user);
        } else {
            $club  = (new Clubs())->get((int) $group);
            $album = (new Albums())->getClubAvatarAlbum($club);
        }

        try {
            $avatar = new Photo();
            $avatar->setOwner((int) $uploader);
            $avatar->setDescription("Profile photo");
            $avatar->setCreated(time());
            $avatar->setFile([
                "tmp_name" => $imagePath,
                "error" => 0,
            ]);
            $avatar->save();
            $album->addPhoto($avatar);
            unlink($imagePath);
        } catch (ImageException | InvalidStateException $e) {
            unlink($imagePath);
            $this->fail(129, "Invalid image file");
        }

        return (object) [
            "photo_hash" => null,
            "photo_src"  => $avatar->getURL(),
        ];
    }

    public function getWallUploadServer(?int $group_id = null): object
    {
        $this->requireUser();

        $album = null;
        if (!is_null($group_id)) {
            $club = (new Clubs())->get(abs($group_id));
            if (!$club) {
                $this->fail(0o404, "Club not found");
            } elseif (!$club->canBeModifiedBy($this->getUser())) {
                $this->fail(200, "Access: Club can't be 'written' by user");
            }
        } else {
            $album = (new Albums())->getUserWallAlbum($this->getUser());
        }

        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", $group_id ?? 0),
            "album_id"   => $album,
            "user_id"    => $this->getUser()->getId(),
        ];
    }

    public function saveWallPhoto(string $photo, string $hash, int $group_id = 0, ?string $caption = null): array
    {
        $imagePath = $this->getImagePath($photo, $hash, $uploader, $group);
        if ($group_id != $group) {
            $this->fail(8, "group_id doesn't match");
        }

        $album = null;
        if ($group_id != 0) {
            $uploader = (new \openvk\Web\Models\Repositories\Users())->get((int) $uploader);
            $album    = (new Albums())->getUserWallAlbum($uploader);
        }

        try {
            $photo = new Photo();
            $photo->setOwner((int) $uploader);
            $photo->setCreated(time());
            $photo->setFile([
                "tmp_name" => $imagePath,
                "error" => 0,
            ]);

            if (!is_null($caption)) {
                $photo->setDescription($caption);
            }

            $photo->save();
            unlink($imagePath);
        } catch (ImageException | InvalidStateException $e) {
            unlink($imagePath);
            $this->fail(129, "Invalid image file");
        }

        if (!is_null($album)) {
            $album->addPhoto($photo);
        }

        return [
            $photo->toVkApiStruct(),
        ];
    }

    public function getUploadServer(?int $album_id = null): object
    {
        $this->requireUser();

        # Not checking rights to album because save() method will do so anyways
        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", 0, true),
            "album_id"   => $album_id,
            "user_id"    => $this->getUser()->getId(),
        ];
    }

    public function save(string $photos_list, string $hash, int $album_id = 0, ?string $caption = null): object
    {
        $this->requireUser();

        $secret = CHANDLER_ROOT_CONF["security"]["secret"];
        if (!hash_equals(hash_hmac("sha3-224", $photos_list, $secret), $hash)) {
            $this->fail(121, "Incorrect hash");
        }

        $album = null;
        if ($album_id != 0) {
            $album_ = (new Albums())->get($album_id);
            if (!$album_) {
                $this->fail(0o404, "Invalid album");
            } elseif (!$album_->canBeModifiedBy($this->getUser())) {
                $this->fail(15, "Access: Album can't be 'written' by user");
            }

            $album = $album_;
        }

        $pList      = json_decode($photos_list);
        $imagePaths = [];
        foreach ($pList as $pDesc) {
            $imagePaths[] = __DIR__ . "/../../tmp/api-storage/photos/$pDesc->keyholder" . "_$pDesc->resource.oct";
        }

        $images = [];
        try {
            foreach ($imagePaths as $imagePath) {
                $photo = new Photo();
                $photo->setOwner($this->getUser()->getId());
                $photo->setCreated(time());
                $photo->setFile([
                    "tmp_name" => $imagePath,
                    "error" => 0,
                ]);

                if (!is_null($caption)) {
                    $photo->setDescription($caption);
                }

                $photo->save();
                unlink($imagePath);

                if (!is_null($album)) {
                    $album->addPhoto($photo);
                }

                $images[] = $photo->toVkApiStruct();
            }
        } catch (ImageException | InvalidStateException $e) {
            foreach ($imagePaths as $imagePath) {
                unlink($imagePath);
            }

            $this->fail(129, "Invalid image file");
        }

        return (object) [
            "count" => sizeof($images),
            "items" => $images,
        ];
    }

    public function createAlbum(string $title, int $group_id = 0, string $description = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($group_id != 0) {
            $club = (new Clubs())->get((int) $group_id);

            if (!$club || !$club->canBeModifiedBy($this->getUser())) {
                $this->fail(15, "Access denied");
            }
        }

        $album = new Album();
        $album->setOwner(isset($club) ? $club->getId() * -1 : $this->getUser()->getId());
        $album->setName($title);
        $album->setDescription($description);
        $album->setCreated(time());
        $album->save();

        return $album->toVkApiStruct($this->getUser());
    }

    public function editAlbum(int $album_id, int $owner_id, string $title = null, string $description = null, int $privacy = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $album = (new Albums())->getAlbumByOwnerAndId($owner_id, $album_id);

        if (!$album || $album->isDeleted() || $album->isCreatedBySystem()) {
            $this->fail(114, "Invalid album id");
        }
        if (!$album->canBeModifiedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if (!is_null($title) && !empty($title) && !ctype_space($title)) {
            $album->setName($title);
        }
        if (!is_null($description)) {
            $album->setDescription($description);
        }

        try {
            $album->save();
        } catch (\Throwable $e) {
            return 1;
        }

        return 1;
    }

    public function getAlbums(int $owner_id = null, string $album_ids = "", int $offset = 0, int $count = 100, bool $need_system = true, bool $need_covers = true, bool $photo_sizes = false)
    {
        $this->requireUser();

        $res = [
            "count" => 0,
            "items" => [],
        ];
        $albums_list = [];
        if ($owner_id == null && empty($album_ids)) {
            $owner_id = $this->getUser()->getId();
        }

        if (empty($album_ids)) {
            $owner = get_entity_by_id($owner_id);
            if (!$owner || !$owner->canBeViewedBy($this->getUser())) {
                $this->fail(15, "Access denied");
            }
            if ($owner_id > 0 && !$owner->getPrivacyPermission('photos.read', $this->getUser())) {
                $this->fail(15, "Access denied");
            }

            $albums_list = null;
            if ($owner_id > 0) {
                # TODO rewrite to offset
                $albums_list = array_slice(iterator_to_array((new Albums())->getUserAlbums($owner, 1, $count + $offset)), $offset);
                $res["count"] = (new Albums())->getUserAlbumsCount($owner);
            } else {
                $albums_list = array_slice(iterator_to_array((new Albums())->getClubAlbums($owner, 1, $count + $offset)), $offset);
                $res["count"] = (new Albums())->getClubAlbumsCount($owner);
            }
        } else {
            $album_ids = explode(',', $album_ids);
            foreach ($album_ids as $album_id) {
                $album = (new Albums())->getAlbumByOwnerAndId((int) $owner_id, (int) $album_id);
                if (!$album || $album->isDeleted() || !$album->canBeViewedBy($this->getUser())) {
                    continue;
                }

                $albums_list[] = $album;
            }
        }

        foreach ($albums_list as $album) {
            if (!$need_system && $album->isCreatedBySystem()) { # TODO use queries
                continue;
            }

            $res["items"][] = $album->toVkApiStruct($this->getUser(), $need_covers, $photo_sizes);
        }

        return $res;
    }

    public function getAlbumsCount(int $user_id = null, int $group_id = null)
    {
        $this->requireUser();

        if (is_null($user_id) && is_null($group_id)) {
            $user_id = $this->getUser()->getId();
        }

        if (!is_null($user_id)) {
            $__user = (new UsersRepo())->get($user_id);
            if (!$__user || $__user->isDeleted() || !$__user->getPrivacyPermission('photos.read', $this->getUser())) {
                $this->fail(15, "Access denied");
            }

            return (new Albums())->getUserAlbumsCount($__user);
        }
        if (!is_null($group_id)) {
            $__club = (new Clubs())->get($group_id);
            if (!$__club || !$__club->canBeViewedBy($this->getUser())) {
                $this->fail(15, "Access denied");
            }

            return (new Albums())->getClubAlbumsCount($__club);
        }

        return 0;
    }

    public function getById(string $photos, bool $extended = false, bool $photo_sizes = false)
    {
        $this->requireUser();

        $photos_splitted_list = explode(",", $photos);
        $res = [];
        if (sizeof($photos_splitted_list) > 78) {
            $this->fail(-78, "Photos count must not exceed limit");
        }

        foreach ($photos_splitted_list as $photo_id) {
            $photo_s_id = explode("_", $photo_id);
            $photo = (new PhotosRepo())->getByOwnerAndVID((int) $photo_s_id[0], (int) $photo_s_id[1]);
            if (!$photo || $photo->isDeleted() || !$photo->canBeViewedBy($this->getUser())) {
                continue;
            }

            $res[] = $photo->toVkApiStruct($photo_sizes, $extended);
        }

        return $res;
    }

    public function get(int $owner_id, int $album_id, string $photo_ids = "", bool $extended = false, bool $photo_sizes = false, int $offset = 0, int $count = 10)
    {
        $this->requireUser();

        $res = [];

        if (empty($photo_ids)) {
            $album = (new Albums())->getAlbumByOwnerAndId($owner_id, $album_id);
            if (!$album || $album->isDeleted() || !$album->canBeViewedBy($this->getUser())) {
                $this->fail(15, "Access denied");
            }

            $photos = array_slice(iterator_to_array($album->getPhotos(1, $count + $offset)), $offset);
            $res["count"] = $album->size();

            foreach ($photos as $photo) {
                if (!$photo || $photo->isDeleted()) {
                    continue;
                }

                $res["items"][] = $photo->toVkApiStruct($photo_sizes, $extended);
            }

        } else {
            $photos = array_unique(explode(',', $photo_ids));
            if (sizeof($photos) > 78) {
                $this->fail(-78, "Photos count must not exceed limit");
            }

            $res = [
                "count" => sizeof($photos),
                "items" => [],
            ];

            foreach ($photos as $photo) {
                $id = explode("_", $photo);

                $photo_entity = (new PhotosRepo())->getByOwnerAndVID((int) $id[0], (int) $id[1]);
                if (!$photo_entity || $photo_entity->isDeleted() || !$photo_entity->canBeViewedBy($this->getUser())) {
                    continue;
                }

                $res["items"][] = $photo_entity->toVkApiStruct($photo_sizes, $extended);
            }
        }

        return $res;
    }

    public function deleteAlbum(int $album_id, int $group_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $album = (new Albums())->get($album_id);

        if (!$album || $album->isDeleted() || $album->isCreatedBySystem() || !$album->canBeModifiedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $album->delete();

        return 1;
    }

    public function edit(int $owner_id, int $photo_id, string $caption = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $photo = (new PhotosRepo())->getByOwnerAndVID($owner_id, $photo_id);

        if (!$photo || $photo->isDeleted() || !$photo->canBeModifiedBy($this->getUser())) {
            $this->fail(21, "Access denied");
        }

        if (!empty($caption)) {
            $photo->setDescription($caption);
            $photo->save();
        }

        return 1;
    }

    public function delete(int $owner_id = null, int $photo_id = null, string $photos = null)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (!$owner_id) {
            $owner_id = $this->getUser()->getId();
        }

        if (is_null($photos)) {
            if (is_null($photo_id)) {
                return 0;
            }

            $photo = (new PhotosRepo())->getByOwnerAndVID($owner_id, $photo_id);
            if (!$photo || $photo->isDeleted() || !$photo->canBeModifiedBy($this->getUser())) {
                return 1;
            }

            $photo->delete();
        } else {
            $photos_list = array_unique(explode(',', $photos));
            if (sizeof($photos_list) > 10) {
                $this->fail(-78, "Photos count must not exceed limit");
            }

            foreach ($photos_list as $photo_id) {
                $id = explode("_", $photo_id);
                $photo = (new PhotosRepo())->getByOwnerAndVID((int) $id[0], (int) $id[1]);
                if (!$photo || $photo->isDeleted() || !$photo->canBeModifiedBy($this->getUser())) {
                    continue;
                }

                $photo->delete();
            }
        }

        return 1;
    }

    # Поскольку комментарии едины, можно использовать метод "wall.deleteComment".
    /*public function deleteComment(int $comment_id, int $owner_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $comment = (new CommentsRepo())->get($comment_id);
        if (!$comment) {
            $this->fail(21, "Invalid comment");
        }

        if (!$comment->canBeModifiedBy($this->getUser())) {
            $this->fail(21, "Access denied");
        }

        $comment->delete();

        return 1;
    }*/

    public function createComment(int $owner_id, int $photo_id, string $message = "", bool $from_group = false)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message) && empty($attachments)) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        $photo = (new PhotosRepo())->getByOwnerAndVID($owner_id, $photo_id);

        if (!$photo || $photo->isDeleted() || !$photo->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $comment = new Comment();
        $comment->setOwner($this->getUser()->getId());
        $comment->setModel(get_class($photo));
        $comment->setTarget($photo->getId());
        $comment->setContent($message);
        $comment->setCreated(time());
        $comment->save();

        return $comment->getId();
    }

    public function getAll(int $owner_id, bool $extended = false, int $offset = 0, int $count = 100, bool $photo_sizes = false)
    {
        $this->requireUser();

        if ($owner_id < 0) {
            $this->fail(-413, "Clubs are not supported");
        }

        $user = (new UsersRepo())->get($owner_id);
        if (!$user || !$user->getPrivacyPermission('photos.read', $this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $photos = (new PhotosRepo())->getEveryUserPhoto($user, $offset, $count);
        $res = [
            "count" => (new PhotosRepo())->getUserPhotosCount($user),
            "items" => [],
        ];

        foreach ($photos as $photo) {
            if (!$photo || $photo->isDeleted()) {
                continue;
            }
            $res["items"][] = $photo->toVkApiStruct($photo_sizes, $extended);
        }

        return $res;
    }

    public function getComments(int $owner_id, int $photo_id, bool $need_likes = false, int $offset = 0, int $count = 100, bool $extended = false, string $fields = "")
    {
        $this->requireUser();

        $photo = (new PhotosRepo())->getByOwnerAndVID($owner_id, $photo_id);
        $comms = array_slice(iterator_to_array($photo->getComments(1, $offset + $count)), $offset);

        if (!$photo || $photo->isDeleted() || !$photo->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $res = [
            "count" => sizeof($comms),
            "items" => [],
        ];

        foreach ($comms as $comment) {
            $res["items"][] = $comment->toVkApiStruct($this->getUser(), $need_likes, $extended);
            if ($extended) {
                if ($comment->getOwner() instanceof \openvk\Web\Models\Entities\User) {
                    $res["profiles"][] = $comment->getOwner()->toVkApiStruct();
                }
            }
        }

        return $res;
    }
}
