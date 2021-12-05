<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\RowModel;
use Nette\Database\Table\ActiveRow;

abstract class Repository
{
    protected $context;
    protected $table;
    
    protected $tableName;
    protected $modelName;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->table   = $this->context->table($this->tableName);
    }
    
    function toEntity(?ActiveRow $ar)
    {
        $entityName = "openvk\\Web\\Models\\Entities\\$this->modelName";
        return is_null($ar) ? NULL : new $entityName($ar);
    }
    
    function get(int $id)
    {
        return $this->toEntity($this->table->get($id));
    }
    
    function size(bool $withDeleted = false): int
    {
        return sizeof($this->table->where("deleted", $withDeleted));
    }
    
    function enumerate(int $page, ?int $perPage = NULL, bool $withDeleted = false): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        
        foreach($this->table->where("deleted", $withDeleted)->page($page, $perPage) as $entity)
            yield $this->toEntity($entity);
    }
    
    use \Nette\SmartObject;
}
