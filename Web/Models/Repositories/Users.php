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
    
    function getByChandlerUser(ChandlerUser $user): ?User
    {
        return $this->toUser($this->users->where("user", $user->getId())->fetch());
    }
    
    function find(string $query, array $pars = [], string $sort = "id DESC"): Util\EntityStream
    {
        $query  = "%$query%";
        $result = $this->users->where("CONCAT_WS(' ', first_name, last_name, pseudo, shortcode) LIKE ?", $query)->where("deleted", 0);
        
        $notNullParams = [];
        $nnparamsCount = 0;
        
        foreach($pars as $paramName => $paramValue) {
            if($paramName != "before" && $paramName != "after" && $paramName != "gender")
                $paramValue != NULL ? $notNullParams += ["$paramName" => "%$paramValue%"] : NULL;
            else
                $paramValue != NULL ? $notNullParams += ["$paramName" => "$paramValue"]   : NULL;
        }

        $nnparamsCount = sizeof($notNullParams);

        if($nnparamsCount > 0) {
            !is_null($notNullParams["hometown"])      ? $result->where("hometown LIKE ?", $notNullParams["hometown"])            : NULL;
            !is_null($notNullParams["city"])          ? $result->where("city LIKE ?", $notNullParams["city"])                    : NULL;
            !is_null($notNullParams["maritalstatus"]) ? $result->where("marital_status LIKE ?", $notNullParams["maritalstatus"]) : NULL;
            !is_null($notNullParams["status"])        ? $result->where("status LIKE ?", $notNullParams["status"])                : NULL;
            !is_null($notNullParams["politViews"])    ? $result->where("polit_views LIKE ?", $notNullParams["politViews"])       : NULL;
            !is_null($notNullParams["email"])         ? $result->where("email_contact LIKE ?", $notNullParams["email"])          : NULL;
            !is_null($notNullParams["telegram"])      ? $result->where("telegram LIKE ?", $notNullParams["telegram"])            : NULL;
            !is_null($notNullParams["site"])          ? $result->where("website LIKE ?", $notNullParams["site"])                 : NULL;
            !is_null($notNullParams["address"])       ? $result->where("address LIKE ?", $notNullParams["address"])              : NULL;
            !is_null($notNullParams["is_online"])     ? $result->where("online >= ?", time() - 900)                              : NULL;
            !is_null($notNullParams["interests"])     ? $result->where("interests LIKE ?", $notNullParams["interests"])          : NULL;
            !is_null($notNullParams["fav_mus"])       ? $result->where("fav_music LIKE ?", $notNullParams["fav_mus"])            : NULL;
            !is_null($notNullParams["fav_films"])     ? $result->where("fav_films LIKE ?", $notNullParams["fav_films"])          : NULL;
            !is_null($notNullParams["fav_shows"])     ? $result->where("fav_shows LIKE ?", $notNullParams["fav_shows"])          : NULL;
            !is_null($notNullParams["fav_books"])     ? $result->where("fav_books LIKE ?", $notNullParams["fav_books"])          : NULL;
            !is_null($notNullParams["fav_quote"])     ? $result->where("fav_quote LIKE ?", $notNullParams["fav_quote"])          : NULL;
            !is_null($notNullParams["before"])        ? $result->where("UNIX_TIMESTAMP(since) < ?", $notNullParams["before"])    : NULL;
            !is_null($notNullParams["after"])         ? $result->where("UNIX_TIMESTAMP(since) > ?", $notNullParams["after"])     : NULL;
            !is_null($notNullParams["gender"])        ? $result->where("sex ?", $notNullParams["gender"])                        : NULL;
            # !is_null($notNullParams["has_avatar"])    ? $result->related(): NULL;
        }
        return new Util\EntityStream("User", $result->order($sort));
    }
    
    function getStatistics(): object
    {
        return (object) [
            "all"    => sizeof(clone $this->users),
            "active" => sizeof((clone $this->users)->where("online > 0")),
            "online" => sizeof((clone $this->users)->where("online >= ?", time() - 900)),
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
