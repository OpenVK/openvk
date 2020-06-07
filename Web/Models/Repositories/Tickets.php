<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Ticket;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Tickets
{
    private $context;
    private $tickets;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->tickets = $this->context->table("tickets");
    }
    
    private function toTicket(?ActiveRow $ar): ?Ticket
    {
        return is_null($ar) ? NULL : new Ticket($ar);
    }
    
    function getTickets(int $state = 0, int $page = 1): \Traversable
    {
        foreach($this->tickets->where(["deleted" => 0, "type" => $state])->page($page, OPENVK_DEFAULT_PER_PAGE) as $t)
            yield new Ticket($t);
    }
    
    function getTicketCount(int $state = 0): int
    {
        return sizeof($this->tickets->where(["deleted" => 0, "type" => $state]));
    }
    
    function getTicketsByuId(int $user_id): \Traversable
    {
        foreach($this->tickets->where(['user_id' => $user_id, 'deleted' => 0]) as $ticket) yield new Ticket($ticket);
    }
    
    function getRequestById(int $req_id): ?Ticket
    {
        $requests = $this->tickets->where(['id' => $req_id])->fetch();
        if(!is_null($requests))
        
            return new Req($requests);
        else
            return null;
        
    }
    
    function get(int $id): ?Ticket
    {
        return $this->toTicket($this->tickets->get($id));
    }
   
    use \Nette\SmartObject;
}
