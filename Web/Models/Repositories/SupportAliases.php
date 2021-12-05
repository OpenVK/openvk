<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;

class SupportAliases extends Repository
{
    protected $tableName = "support_names";
    protected $modelName = "SupportAlias";
    
    function get(int $agent)
    {
        return $this->toEntity($this->table->where("agent", $agent)->fetch());
    }
}
