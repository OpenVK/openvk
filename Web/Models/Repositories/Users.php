<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class Users
{
    private $context;
    private $users;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->users   = $this->context->table("profiles");
    }
    
    private function toUser(?ActiveRow $ar): ?User
    {
        return is_null($ar) ? NULL : new User($ar);
    }
    
    function get(int $id): ?User
    {
        return $this->toUser($this->users->get($id));
    }
    
    function getByShortURL(string $url): ?User
    {
        return $this->toUser($this->users->where("shortcode", $url)->fetch());
    }
    
    function getByChandlerUser(ChandlerUser $user): ?User
    {
        return $this->toUser($this->users->where("user", $user->getId())->fetch());
    }
    
    function find(string $query, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $query   = "$query%";
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        foreach($this->users->where("first_name LIKE ? OR last_name LIKE ?", $query,$query)->page($page, $perPage) as $result)
            yield new User($result);
    }
    
    function getFoundCount(string $query): int
    {
        $query = "$query%";
        return sizeof($this->users->where("first_name LIKE ? OR last_name LIKE ?", $query, $query));
    }
    
    function getStatistics(): object
    {
        return (object) [
            "all"    => sizeof(clone $this->users),
            "active" => sizeof((clone $this->users)->where("online > 0")),
            "online" => sizeof((clone $this->users)->where("online >= ?", time() - 900)),
        ];
    }
    
    use \Nette\SmartObject;
}
