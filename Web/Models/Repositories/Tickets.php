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

    private static $cache = [];
    private static array $countCache = [];

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
        return $this->tickets->where(["deleted" => 0, "type" => $state])->count("*");
    }

    public function getTicketsByUserId(int $userId, int $page = 1): \Traversable
    {
        foreach ($this->tickets->where(["user_id" => $userId, "deleted" => 0])->order("created DESC")->page($page, OPENVK_DEFAULT_PER_PAGE) as $ticket) {
            yield new Ticket($ticket);
        }
    }

    public function getTicketsCountByUserId(int $userId, ?int $type = null): int
    {
        $key = $userId . ":" . ($type ?? "*");
        if (!array_key_exists($key, self::$countCache)) {
            $filter = ["user_id" => $userId, "deleted" => 0];
            if (!is_null($type)) {
                $filter["type"] = $type;
            }

            self::$countCache[$key] = (int) $this->tickets->where($filter)->count("*");
        }

        return self::$countCache[$key];
    }

    public function getRequestById(int $requestId): ?Ticket
    {
        $requests = $this->tickets->where(["id" => $requestId])->fetch();
        if (!is_null($requests)) {
            return new Ticket($requests);
        } else {
            return null;
        }

    }

    public function get(int $id): ?Ticket
    {
        return self::$cache[$id] ??= $this->toTicket($this->tickets->get($id));
    }
}
