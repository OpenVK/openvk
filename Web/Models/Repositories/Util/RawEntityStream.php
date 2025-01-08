<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories\Util;
use Nette\Database\Row;

class RawEntityStream extends EntityStream
{
    function __construct(string $repo, string $sql, ...$db_params)
    {
        $this->sqlQuery    = $sql;
        $this->entityRepo  = new ($class[0] === "\\" ? $repo : "openvk\\Web\\Models\\Repositories\\$repo"."s");
        $this->dbParams    = $db_params;
    }

    private function dbs(int $page = 0, ?int $perPage = NULL): \Traversable
    {
        if(!$this->dbQuery) {
            $this->dbParams[] = $perPage;
            $this->dbParams[] = (($page - 1) * $perPage);

            $this->dbQuery = \Chandler\Database\DatabaseConnection::i()->getConnection()->query($this->sqlQuery, ...$this->dbParams);
        }

        return $this->dbQuery;
    }

    private function getEntity(Row $result)
    {
        $repo = new $this->entityRepo;
        return $repo->get($result->id);
    }

    protected function stream(\Traversable $iterator): \Traversable
    {
        foreach($iterator as $result)
            yield $this->getEntity($result);
    }

    function page(int $page, ?int $perPage = NULL): \Traversable
    {
        $fetchedRows = $this->dbs($page, $perPage);

        return $this->stream($fetchedRows);
    }

    function size(): int
    {
        bdump($this->dbs()->getRowCount());
        return $this->dbs()->getRowCount();
    }
}
