<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Video;
use Chandler\Database\DatabaseConnection;

class Videos
{
    private $context;
    private $videos;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->videos  = $this->context->table("videos");
    }
    
    function get(int $id): ?Video
    {
        $videos = $this->videos->get($id);
        if(!$videos) return NULL;
        
        return new Video($videos);
    }
    
    function getByOwnerAndVID(int $owner, int $vId): ?Video
    {
        $videos = $this->videos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();
        if(!$videos) return NULL;
        
        return new Video($videos);
    }
    
    function getByUser(User $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        foreach($this->videos->where("owner", $user->getId())->where("deleted", 0)->page($page, $perPage) as $video)
            yield new Video($video);
    }
    
    function getUserVideosCount(User $user): int
    {
        return sizeof($this->videos->where("owner", $user->getId())->where("deleted", 0));
    }
}
