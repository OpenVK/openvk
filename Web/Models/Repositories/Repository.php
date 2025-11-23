<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\RowModel;
use Nette\Database\Table\ActiveRow;

abstract class Repository
{
    use \Nette\SmartObject;
    protected $context;
    protected $table;

    protected $tableName;
    protected $modelName;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->table   = $this->context->table($this->tableName);
    }

    public function toEntity(?ActiveRow $ar)
    {
        $entityName = "openvk\\Web\\Models\\Entities\\$this->modelName";
        return is_null($ar) ? null : new $entityName($ar);
    }

    public function get(int $id)
    {
        return $this->toEntity($this->table->get($id));
    }

    public function size(bool $withDeleted = false): int
    {
        return $this->table->where("deleted", $withDeleted)->count("*");
    }

    public function enumerate(int $page, ?int $perPage = null, bool $withDeleted = false): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;

        foreach ($this->table->where("deleted", $withDeleted)->page($page, $perPage) as $entity) {
            yield $this->toEntity($entity);
        }
    }
}
