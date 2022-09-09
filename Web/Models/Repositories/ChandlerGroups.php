<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Entities\User;

class ChandlerGroups
{
    private $context;
    private $groups;

    public function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->groups  = $this->context->table("chandlergroups");
        $this->members = $this->context->table("chandleraclrelations");
        $this->perms   = $this->context->table("chandleraclgroupspermissions");
    }

    function get(string $UUID): ?ActiveRow
    {
        return $this->groups->where("id", $UUID)->fetch();
    }

    function getList(): \Traversable
    {
        foreach($this->groups as $group) yield $group;
    }

    function getMembersById(string $UUID): \Traversable
    {
        foreach($this->members->where("group", $UUID) as $member) yield $member;
    }

    function getPermissionsById(string $UUID): \Traversable
    {
        foreach($this->perms->where("group", $UUID) as $perm) yield $perm;
    }
}
