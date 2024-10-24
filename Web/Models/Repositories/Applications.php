<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\Application;
use openvk\Web\Models\Entities\User;

class Applications
{
    private $context;
    private $apps;
    private $appRels;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->apps    = $this->context->table("apps");
        $this->appRels = $this->context->table("app_users");
    }
    
    private function toApp(?ActiveRow $ar): ?Application
    {
        return is_null($ar) ? NULL : new Application($ar);
    }
    
    function get(int $id): ?Application
    {
        return $this->toApp($this->apps->get($id));
    }
    
    function getList(int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $apps    = $this->apps->where("enabled", 1)->page($page, $perPage);
        foreach($apps as $app)
            yield new Application($app);
    }
    
    function getListCount(): int
    {
        return sizeof($this->apps->where("enabled", 1));
    }
    
    function getByOwner(User $owner, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $apps    = $this->apps->where("owner", $owner->getId())->page($page, $perPage);
        foreach($apps as $app)
            yield new Application($app);
    }
    
    function getOwnCount(User $owner): int
    {
        return sizeof($this->apps->where("owner", $owner->getId()));
    }
    
    function getInstalled(User $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $apps    = $this->appRels->where("user", $user->getId())->page($page, $perPage);
        foreach($apps as $appRel)
            yield $this->get($appRel->app);
    }
    
    function getInstalledCount(User $user): int
    {
        return sizeof($this->appRels->where("user", $user->getId()));
    }

    function find(string $query = "", array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $query = "%$query%";
        $result = $this->apps->where("CONCAT_WS(' ', name, description) LIKE ?", $query)->where("enabled", 1);
        $order_str = 'id';

        switch($order['type']) {
            case 'id':
                $order_str = 'id ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        if($order_str)
            $result->order($order_str);
        
        return new Util\EntityStream("Application", $result);
    }
}