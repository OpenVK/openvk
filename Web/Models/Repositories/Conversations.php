<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\{Messages as M, User};
use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\ActiveRow;

class Conversations
{
    private $context;
    private $convos;
    
    function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->convos  = $this->context->table("conversations");
    }
    
    private function toConversation(?ActiveRow $ar): ?M\AbstractConversation
    {
        if(is_null($ar))
            return NULL;
        else if($ar->is_pm)
            return new M\PrivateConversation($ar);
        else
            return new M\Conversation($ar);
    }
    
    function get(int $id): ?M\AbstractConversation
    {
        return $this->toConversation($this->convos->get($id));
    }
    
    function getConversationsByUser(User $user, int $page = 1, ?int $perPage = NULL) : \Traversable
    {
        $rels = $this->context->table("conversation_members")->where([
            "deleted"   => false,
            "user"      => $user->getId(),
        ])->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        foreach($rels as $rel)
            yield $this->get($rel->conversation);
    }
    
    function getPrivateConversation(User $user, int $peer): M\PrivateConversation
    {
        ;
    }
}
