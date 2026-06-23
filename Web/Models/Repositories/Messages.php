<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Message;
use openvk\Web\Models\Entities\Correspondence;
use Chandler\Database\DatabaseConnection;

class Messages
{
    private $context;
    private $messages;

    /* aggressive sql caching */
    private static $cache = [];

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->messages = $this->context->table("messages");
    }

    private function toMessage(?ActiveRow $ar): ?Message
    {
        return is_null($ar) ? null : new Message($ar);
    }

    public function get(int $id): ?Message
    {
        return self::$cache[$id] ??= $this->toMessage($this->messages->get($id));
    }

    public function getCorrespondencies(RowModel $correspondent, int $page = 1, ?int $perPage = null, ?int $offset = null): \Traversable
    {
        $id      = $correspondent->getId();
        $class   = get_class($correspondent);
        $limit   = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $offset ??= ($page - 1) * $limit;
        $query   = file_get_contents(__DIR__ . "/../sql/get-correspondencies.tsql");
        DatabaseConnection::i()->getConnection()->query(file_get_contents(__DIR__ . "/../sql/mysql-msg-fix.tsql"));
        $coresps = DatabaseConnection::i()->getConnection()->query($query, $id, $class, $id, $class, $limit, $offset);
        foreach ($coresps as $c) {
            if ($c->class === 'openvk\Web\Models\Entities\User') {
                $anotherCorrespondent = (new Users())->get($c->id);
            } elseif ($c->class === 'openvk\Web\Models\Entities\Club') {
                $anotherCorrespondent = (new Clubs())->get($c->id);
            }

            yield new Correspondence($correspondent, $anotherCorrespondent);
        }
    }

    public function getCorrespondenciesCount(RowModel $correspondent): ?int
    {
        $id    = $correspondent->getId();
        $class = get_class($correspondent);
        $query = file_get_contents(__DIR__ . "/../sql/get-correspondencies-count.tsql");
        DatabaseConnection::i()->getConnection()->query(file_get_contents(__DIR__ . "/../sql/mysql-msg-fix.tsql"));
        $count = DatabaseConnection::i()->getConnection()->query($query, $id, $class, $id, $class)->fetch()->cnt;
        return $count;
    }
}
