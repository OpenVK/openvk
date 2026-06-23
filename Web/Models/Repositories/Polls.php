<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Poll;

class Polls
{
    private $polls;

    private static $cache = [];

    public function __construct()
    {
        $this->polls = DatabaseConnection::i()->getContext()->table("polls");
    }

    private function toPoll(?ActiveRow $ar): ?Poll
    {
        return is_null($ar) ? null : new Poll($ar);
    }

    public function get(int $id): ?Poll
    {
        return self::$cache[$id] ??= $this->toPoll($this->polls->get($id));
    }
}
