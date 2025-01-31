<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\TicketComment;
use Chandler\Database\DatabaseConnection;

class TicketComments
{
    use \Nette\SmartObject;
    private $context;
    private $comments;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->comments = $this->context->table("tickets_comments");
    }

    public function getCommentsById(int $ticket_id): \Traversable
    {
        foreach ($this->comments->where(['ticket_id' => $ticket_id, 'deleted' => 0]) as $comment) {
            yield new TicketComment($comment);
        }
    }

    public function get(int $id): ?TicketComment
    {
        $comment = $this->comments->get($id);
        ;
        if (!is_null($comment)) {
            return new TicketComment($comment);
        } else {
            return null;
        }
    }

    public function getCountByAgent(int $agent_id, int $mark = null): int
    {
        $filter = ['user_id' => $agent_id, 'user_type' => 1];
        $mark && $filter['mark'] = $mark;
        return sizeof($this->comments->where($filter));
    }
}
