<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\RowModel;
use Nette\Database\Table\ActiveRow;

abstract class Repository
{
    private $context;
    private $table;
    
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
    
    use \Nette\SmartObject;
}
