<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\Manager;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Managers
{
    use \Nette\SmartObject;
    private $context;
    private $managers;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->managers = $this->context->table("group_coadmins");
    }

    private function toManager(?ActiveRow $ar): ?Manager
    {
        return is_null($ar) ? null : new Manager($ar);
    }

    public function get(int $id): ?Manager
    {
        return $this->toManager($this->managers->where("id", $id)->fetch());
    }

    public function getByUserAndClub(int $user, int $club): ?Manager
    {
        return $this->toManager($this->managers->where("user", $user)->where("club", $club)->fetch());
    }
}
