<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Photos as PhotosRepo, Albums};

class Photos implements Handler
{
    protected $user;
    protected $photos;
    
    function __construct(?User $user)
    {
        $this->user  = $user;
        $this->photos = new PhotosRepo;
    }

    function getPhotos(int $page = 1, int $album = 0, callable $resolve, callable $reject)
    {
        if($album == 0) {
            $photos = $this->photos->getEveryUserPhoto($this->user, $page, 24);
            $count  = $this->photos->getUserPhotosCount($this->user);
        } else {
            $album = (new Albums)->get($album);

            if(!$album || $album->isDeleted() || $album->getOwner()->getId() != $this->user->getId())
                $reject(55, "Invalid .");

            $photos = $album->getPhotos($page, 24);
            $count  = $album->size();
        }

        $arr = [
            "count"  => $count,
            "items"  => [],
        ];

        foreach($photos as $photo) {
            $res = json_decode(json_encode($photo->toVkApiStruct()), true);
            
            $arr["items"][] = $res;
        }

        $resolve($arr);
    }

    function getAlbums(callable $resolve, callable $reject)
    {
        $albumsRepo = (new Albums);
        $count  = $albumsRepo->getUserAlbumsCount($this->user);
        $albums = $albumsRepo->getUserAlbums($this->user, 1, $count);

        $arr = [
            "count"  => $count,
            "items"  => [],
        ];

        foreach($albums as $album) {
            $res = ["id" => $album->getId(), "name" => $album->getName()];
            
            $arr["items"][] = $res;
        }

        $resolve($arr);
    }
}
