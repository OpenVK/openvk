<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories\Util;
use Nette\Database\Table\ActiveRow;

class EntityStream implements \IteratorAggregate
{
    private $dbStream;
    private $entityClass;
    
    function __construct(string $class, \Traversable $dbStream)
    {
        $this->dbStream    = $dbStream;
        $this->entityClass = $class[0] === "\\" ? $class : "openvk\\Web\\Models\\Entities\\$class";
    }
    
    /**
     * Almost shorthand for (clone $this->dbStream)
     * Needed because it's used often in this class. And it's used often to prevent changing mutable dbStream.
     */
    private function dbs(): \Traversable
    {
        return (clone $this->dbStream);
    }
    
    private function getEntity(ActiveRow $result)
    {
        return new $this->entityClass($result);
    }
    
    private function stream(\Traversable $iterator): \Traversable
    {
        foreach($iterator as $result)
            yield $this->getEntity($result);
    }
    
    function getIterator(): \Traversable
    {
        trigger_error("Trying to use EntityStream as iterator directly. Are you sure this is what you want?", E_USER_WARNING);
        
        return $this->stream($this->dbs());
    }
    
    function page(int $page, ?int $perPage = NULL): \Traversable
    {
        return $this->stream($this->dbs()->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE));
    }
    
    function offsetLimit(int $offset = 0, ?int $limit = NULL): \Traversable
    {
        return $this->stream($this->dbs()->limit($limit ?? OPENVK_DEFAULT_PER_PAGE, $offset));
    }
    
    function size(): int
    {
        return sizeof($this->dbs());
    }
}
