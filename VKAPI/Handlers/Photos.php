<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

use Nette\InvalidStateException;
use Nette\Utils\ImageException;
use openvk\Web\Models\Entities\Photo;
use openvk\Web\Models\Repositories\Albums;
use openvk\Web\Models\Repositories\Clubs;

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

    private function getImagePath(string $photo, string $hash, ?string& $up = NULL, ?string& $group = NULL): string
    {
        $secret = CHANDLER_ROOT_CONF["security"]["secret"];
        if(!hash_equals(hash_hmac("sha3-224", $photo, $secret), $hash))
            $this->fail(121, "Incorrect hash");

        [$up, $image, $group] = explode("|", $photo);

        $imagePath = __DIR__ . "/../../tmp/api-storage/photos/$up" . "_$image.oct";
        if(!file_exists($imagePath))
            $this->fail(10, "Invalid image");

        return $imagePath;
    }

    function getOwnerPhotoUploadServer(int $owner_id = 0): object
    {
        $this->requireUser();

        if($owner_id < 0) {
            $club = (new Clubs)->get(abs($owner_id));
            if(!$club)
                $this->fail(0404, "Club not found");
            else if(!$club->canBeModifiedBy($this->getUser()))
                $this->fail(200, "Access: Club can't be 'written' by user");
        }

        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", isset($club) ? 0 : $club->getId()),
        ];
    }

    function saveOwnerPhoto(string $photo, string $hash): object
    {
        $imagePath = $this->getImagePath($photo, $hash, $uploader, $group);
        if($group == 0) {
            $user  = (new \openvk\Web\Models\Repositories\Users)->get((int) $uploader);
            $album = (new Albums)->getUserAvatarAlbum($user);
        } else {
            $club  = (new Clubs)->get((int) $group);
            $album = (new Albums)->getClubAvatarAlbum($club);
        }

        try {
            $avatar = new Photo;
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
        } catch(ImageException | InvalidStateException $e) {
            unlink($imagePath);
            $this->fail(129, "Invalid image file");
        }

        return (object) [
            "photo_hash" => NULL,
            "photo_src"  => $avatar->getURL(),
        ];
    }

    function getWallUploadServer(?int $group_id = NULL): object
    {
        $this->requireUser();

        $album = NULL;
        if(!is_null($group_id)) {
            $club = (new Clubs)->get(abs($group_id));
            if(!$club)
                $this->fail(0404, "Club not found");
            else if(!$club->canBeModifiedBy($this->getUser()))
                $this->fail(200, "Access: Club can't be 'written' by user");
        } else {
            $album = (new Albums)->getUserWallAlbum($this->getUser());
        }

        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", $group_id ?? 0),
            "album_id"   => $album,
            "user_id"    => $this->getUser()->getId(),
        ];
    }

    function saveWallPhoto(string $photo, string $hash, int $group_id = 0, ?string $caption = NULL): array
    {
        $imagePath = $this->getImagePath($photo, $hash, $uploader, $group);
        if($group_id != $group)
            $this->fail(8, "group_id doesn't match");

        $album = NULL;
        if($group_id != 0) {
            $uploader = (new \openvk\Web\Models\Repositories\Users)->get((int) $uploader);
            $album    = (new Albums)->getUserWallAlbum($uploader);
        }

        try {
            $photo = new Photo;
            $photo->setOwner((int) $uploader);
            $photo->setCreated(time());
            $photo->setFile([
                "tmp_name" => $imagePath,
                "error" => 0,
            ]);

            if (!is_null($caption))
                $photo->setDescription($caption);

            $photo->save();
            unlink($imagePath);
        } catch(ImageException | InvalidStateException $e) {
            unlink($imagePath);
            $this->fail(129, "Invalid image file");
        }

        if(!is_null($album))
            $album->addPhoto($photo);

        return [
            $photo->toVkApiStruct(),
        ];
    }

    function getUploadServer(?int $album_id = NULL): object
    {
        $this->requireUser();

        # Not checking rights to album because save() method will do so anyways
        return (object) [
            "upload_url" => $this->getPhotoUploadUrl("photo", 0, true),
            "album_id"   => $album_id,
            "user_id"    => $this->getUser()->getId(),
        ];
    }

    function save(string $photos_list, string $hash, int $album_id = 0, ?string $caption = NULL): object
    {
        $this->requireUser();

        $secret = CHANDLER_ROOT_CONF["security"]["secret"];
        if(!hash_equals(hash_hmac("sha3-224", $photos_list, $secret), $hash))
            $this->fail(121, "Incorrect hash");

        $album = NULL;
        if($album_id != 0) {
            $album_ = (new Albums)->get($album_id);
            if(!$album_)
                $this->fail(0404, "Invalid album");
            else if(!$album_->canBeModifiedBy($this->getUser()))
                $this->fail(15, "Access: Album can't be 'written' by user");

            $album = $album_;
        }

        $pList      = json_decode($photos_list);
        $imagePaths = [];
        foreach($pList as $pDesc)
            $imagePaths[] = __DIR__ . "/../../tmp/api-storage/photos/$pDesc->keyholder" . "_$pDesc->resource.oct";

        $images = [];
        try {
            foreach($imagePaths as $imagePath) {
                $photo = new Photo;
                $photo->setOwner($this->getUser()->getId());
                $photo->setCreated(time());
                $photo->setFile([
                    "tmp_name" => $imagePath,
                    "error" => 0,
                ]);

                if (!is_null($caption))
                    $photo->setDescription($caption);

                $photo->save();
                unlink($imagePath);

                if(!is_null($album))
                    $album->addPhoto($photo);

                $images[] = $photo->toVkApiStruct();
            }
        } catch(ImageException | InvalidStateException $e) {
            foreach($imagePaths as $imagePath)
                unlink($imagePath);

            $this->fail(129, "Invalid image file");
        }

        return (object) [
            "count" => sizeof($images),
            "items" => $images,
        ];
    }
}