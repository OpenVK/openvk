<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Photo;
use Chandler\Database\DatabaseConnection;

class Photos
{
    private $context;
    private $photos;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->photos  = $this->context->table("photos");
    }
    
    function get(int $id): ?Photo
    {
        $photo = $this->photos->get($id);
        if(!$photo) return NULL;
        
        return new Photo($photo);
    }
    
    function getByOwnerAndVID(int $owner, int $vId): ?Photo
    {
        $photo = $this->photos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();
        if(!$photo) return NULL;
        
        return new Photo($photo);
    }
}
