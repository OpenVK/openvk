<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
// use openvk\Web\Models\Entities\Ticket;
// use openvk\Web\Models\Entities\User;
// use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Entities\TicketComment;
use Chandler\Database\DatabaseConnection;

class TicketComments
{
    private $context;
    private $comments;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->comments = $this->context->table("tickets_comments");
    }
    
    function getCommentsById(int $ticket_id): \Traversable
    {
        foreach($this->comments->where(['ticket_id' => $ticket_id, 'deleted' => 0]) as $comment) yield new TicketComment($comment);
    }
    
    // private function toTicket(?ActiveRow $ar): ?Ticket
    // {
    //     return is_null($ar) ? NULL : new Ticket($ar);
    // }
    
    // function getTicketsByuId(int $user_id): \Traversable
    // {
    //     foreach($this->tickets->where(['user_id' => $user_id, 'deleted' => 0]) as $ticket) yield new Ticket($ticket);
    // }
    
    // function getRequestById(int $req_id): ?Ticket
    // {
    //     $requests = $this->tickets->where(['id' => $req_id])->fetch();
    //     if(!is_null($requests))
        
    //         return new Req($requests);
    //     else
    //         return null;
        
    // }
    
    // function get(int $id): ?Ticket
    // {
    //     return $this->toTicket($this->tickets->get($id));
    // }

    function get(int $id): ?TicketComment
    {
        $comment = $this->comments->get($id);;
        if (!is_null($comment))
            return new TicketComment($comment);
        else
            return NULL;
    }
   
    use \Nette\SmartObject;
}
