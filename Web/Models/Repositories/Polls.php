<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Poll;

class Polls
{
    private $polls;

    public function __construct()
    {
        $this->polls = DatabaseConnection::i()->getContext()->table("polls");
    }

    public function get(int $id): ?Poll
    {
        $poll = $this->polls->get($id);
        if (!$poll) {
            return null;
        }

        return new Poll($poll);
    }
}
