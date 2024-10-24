<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Aliases;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class Users
{
    private $context;
    private $users;
    private $aliases;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->users   = $this->context->table("profiles");
        $this->aliases = $this->context->table("aliases");
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
        $shortcode = $this->toUser($this->users->where("shortcode", $url)->fetch());

        if ($shortcode)
            return $shortcode;

        $alias = (new Aliases)->getByShortcode($url);

        if (!$alias) return NULL;
        if ($alias->getType() !== "user") return NULL;
        
        return $alias->getUser();
    }
    
    function getByChandlerUserId(string $cid): ?User
    {
        return $this->toUser($this->users->where("user", $cid)->fetch());
    }
    
    function getByChandlerUser(?ChandlerUser $user): ?User
    {
        return $user ? $this->getByChandlerUserId($user->getId()) : NULL;
    }
    
    function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $query = "%$query%";
        $result = $this->users->where("CONCAT_WS(' ', first_name, last_name, pseudo, shortcode) LIKE ?", $query)->where("deleted", 0);
        $order_str = 'id';

        switch($order['type']) {
            case 'id':
            case 'reg_date':
                $order_str = 'id ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
            case 'rating':
                $order_str = 'rating DESC';
                break;
        }

        foreach($params as $paramName => $paramValue) {
            if(is_null($paramValue) || $paramValue == '') continue;

            switch($paramName) {
                case "hometown":
                    $result->where("hometown LIKE ?", "%$paramValue%");
                    break;
                case "city":
                    $result->where("city LIKE ?", "%$paramValue%");
                    break;
                case "marital_status":
                    $result->where("marital_status ?", $paramValue);
                    break;
                case "polit_views":
                    $result->where("polit_views ?", $paramValue);
                    break;
                case "is_online":
                    $result->where("online >= ?", time() - 900);
                    break;
                case "fav_mus":
                    $result->where("fav_music LIKE ?", "%$paramValue%");
                    break;
                case "fav_films":
                    $result->where("fav_films LIKE ?", "%$paramValue%");
                    break;
                case "fav_shows":
                    $result->where("fav_shows LIKE ?", "%$paramValue%");
                    break;
                case "fav_books":
                    $result->where("fav_books LIKE ?", "%$paramValue%");
                    break;
                case "before":
                    $result->where("UNIX_TIMESTAMP(since) < ?", $paramValue);
                    break;
                case "after":
                    $result->where("UNIX_TIMESTAMP(since) > ?", $paramValue);
                    break;
                case "gender":
                    if((int) $paramValue == 3) break;
                    $result->where("sex ?", (int) $paramValue);
                    break;
                case "ignore_id":
                    $result->where("id != ?", $paramValue);
                    break;
                case "ignore_private":
                    $result->where("profile_type", 0);
                    break;
            }
        }

        if($order_str)
            $result->order($order_str);

        return new Util\EntityStream("User", $result);
    }
    
    function getStatistics(): object
    {
        return (object) [
            "all"    => (clone $this->users)->count('*'),
            "active" => (clone $this->users)->where("online > 0")->count('*'),
            "online" => (clone $this->users)->where("online >= ?", time() - 900)->count('*'),
        ];
    }

    function getByAddress(string $address): ?User
    {
        if(substr_compare($address, "/", -1) === 0)
            $address = substr($address, 0, iconv_strlen($address) - 1);

        $serverUrl = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
        if(strpos($address, $serverUrl . "/") === 0)
            $address = substr($address, iconv_strlen($serverUrl) + 1);

        if(strpos($address, "id") === 0) {
            $user = $this->get((int) substr($address, 2));
            if($user) return $user;
        }

        return $this->getByShortUrl($address);
    }

    /**
     * If you need to check if the user is an instance administrator, use `$user->getChandlerUser()->can("access")->model("admin")->whichBelongsTo(NULL)`.
     * This method is more suitable for instance administrators lists
     */
    function getInstanceAdmins(bool $excludeHidden = true): \Traversable
    {
        $query = "SELECT DISTINCT(`profiles`.`id`) FROM `ChandlerACLRelations` JOIN `profiles` ON `ChandlerACLRelations`.`user` = `profiles`.`user` COLLATE utf8mb4_unicode_520_ci WHERE `ChandlerACLRelations`.`group` IN (SELECT `group` FROM `ChandlerACLGroupsPermissions` WHERE `model` = \"admin\" AND `permission` = \"access\")";

        if($excludeHidden)
        $query .= " AND `ChandlerACLRelations`.`user` NOT IN (SELECT `user` FROM `ChandlerACLRelations` WHERE `group` IN (SELECT `group` FROM `ChandlerACLGroupsPermissions` WHERE `model` = \"hidden_admin\" AND `permission` = \"be\"))";

        $query .= " ORDER BY `profiles`.`id`;";

        $result = DatabaseConnection::i()->getConnection()->query($query);
        foreach($result as $entry)
            yield $this->get($entry->id);
    }
    
    use \Nette\SmartObject;
}
