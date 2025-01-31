<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Ticket;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Tickets
{
    use \Nette\SmartObject;
    private $context;
    private $tickets;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->tickets = $this->context->table("tickets");
    }

    private function toTicket(?ActiveRow $ar): ?Ticket
    {
        return is_null($ar) ? null : new Ticket($ar);
    }

    public function getTickets(int $state = 0, int $page = 1): \Traversable
    {
        foreach ($this->tickets->where(["deleted" => 0, "type" => $state])->order("created DESC")->page($page, OPENVK_DEFAULT_PER_PAGE) as $ticket) {
            yield new Ticket($ticket);
        }
    }

    public function getTicketCount(int $state = 0): int
    {
        return sizeof($this->tickets->where(["deleted" => 0, "type" => $state]));
    }

    public function getTicketsByUserId(int $userId, int $page = 1): \Traversable
    {
        foreach ($this->tickets->where(["user_id" => $userId, "deleted" => 0])->order("created DESC")->page($page, OPENVK_DEFAULT_PER_PAGE) as $ticket) {
            yield new Ticket($ticket);
        }
    }

    public function getTicketsCountByUserId(int $userId, int $type = null): int
    {
        if (is_null($type)) {
            return sizeof($this->tickets->where(["user_id" => $userId, "deleted" => 0]));
        } else {
            return sizeof($this->tickets->where(["user_id" => $userId, "deleted" => 0, "type" => $type]));
        }
    }

    public function getRequestById(int $requestId): ?Ticket
    {
        $requests = $this->tickets->where(["id" => $requestId])->fetch();
        if (!is_null($requests)) {
            return new Req($requests);
        } else {
            return null;
        }

    }

    public function get(int $id): ?Ticket
    {
        return $this->toTicket($this->tickets->get($id));
    }
}
