<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories\Util;

use Nette\Database\Row;
use Chandler\Database\DatabaseConnection;

class RawEntityStream implements \IteratorAggregate
{
    private string $query;
    private array $bindings;
    private string $entityClass;

    private $repository;

    public function __construct(string $class, string $query, array $bindings = [], $repository = null)
    {
        $this->entityClass = $class[0] === "\\" ? $class : "openvk\\Web\\Models\\Entities\\$class";

        $this->query = $query;
        $this->bindings = $bindings;

        $this->repository = $repository;
    }

    private function dbs($query = null, $bindings = []): \Traversable
    {
        $query ??= $this->query;
        $bindings = array_merge($this->bindings, $bindings);

        return DatabaseConnection::i()->getConnection()->query($query, ...$bindings);
    }

    private function getEntity(Row $result)
    {
        return $this->repository->get($result->id);
    }

    private function stream(\Traversable $iterator): \Traversable
    {
        foreach ($iterator as $result) {
            yield $this->getEntity($result);
        }
    }

    public function getIterator(): \Traversable
    {
        trigger_error("Trying to use EntityStream as iterator directly. Are you sure this is what you want?", E_USER_WARNING);

        return $this->stream($this->dbs());
    }

    public function page(int $page, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        return $this->stream($this->dbs($this->query . " limit ? offset ?", [$perPage, ($page - 1) * $perPage]));
    }

    public function size(): int
    {
        $result = $this->dbs("select count(*) as cnt from ({$this->query}) as q")->fetch();
        return $result->cnt;
    }
}
