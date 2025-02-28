<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{User, SupportAgent};

class SupportAgents
{
    private $context;
    private $agents;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->agents  = $this->context->table("support_names");
    }

    private function toAgent(?ActiveRow $ar)
    {
        return is_null($ar) ? null : new SupportAgent($ar);
    }

    public function get(int $id): ?SupportAgent
    {
        return $this->toAgent($this->agents->where("agent", $id)->fetch());
    }

    public function isExists(int $id): bool
    {
        return !is_null($this->get($id));
    }
}
