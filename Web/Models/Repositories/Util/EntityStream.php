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
        $this->entityClass = "openvk\\Web\\Models\\Entities\\$class";
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
        
        return $this->stream($this->dbStream);
    }
    
    function page(int $page, ?int $perPage = NULL): \Traversable
    {
        return $this->stream($this->dbStream->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE));
    }
    
    function offsetLimit(int $offset = 0, ?int $limit = NULL): \Traversable
    {
        return $this->stream($this->dbStream->limit($limit ?? OPENVK_DEFAULT_PER_PAGE, $offset));
    }
    
    function size(): int
    {
        return sizeof(clone $this->dbStream);
    }
}
