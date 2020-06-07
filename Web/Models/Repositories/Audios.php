<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Audio;
use Chandler\Database\DatabaseConnection;

class Audios
{
    private $context;
    private $audios;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->audios  = $this->context->table("audios");
        $this->rels    = $this->context->table("audio_relations");
    }
    
    function get(int $id): ?Video
    {
        $videos = $this->videos->get($id);
        if(!$videos) return NULL;
        
        return new Audio($videos);
    }
    
    function getByUser(User $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        foreach($this->rels->where("user", $user->getId())->page($page, $perPage) as $rel)
            yield $this->get($rel->audio);
    }
    
    function getUserAudiosCount(User $user): int
    {
        return sizeof($this->rels->where("user", $user->getId()));
    }
}
