<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Club;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Clubs
{
    private $context;
    private $clubs;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->clubs   = $this->context->table("groups");
    }
    
    private function toClub(?ActiveRow $ar): ?Club
    {
        return is_null($ar) ? NULL : new Club($ar);
    }
    
    function getByShortURL(string $url): ?Club
    {
        return $this->toClub($this->clubs->where("shortcode", $url)->fetch());
    }
    
    function get(int $id): ?Club
    {
        return $this->toClub($this->clubs->get($id));
    }
    
    function find(string $query, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $query  = "%$query%";
        $result = $this->clubs->where("name LIKE ? OR about LIKE ?", $query, $query);
        
        return new Util\EntityStream("Club", $result);
    }

    function getCount(): int
    {
        return sizeof(clone $this->clubs);
    }

    function getPopularClubs(): \Traversable
    {
        $query   = "SELECT ROW_NUMBER() OVER (ORDER BY `subscriptions` DESC) as `place`, `target` as `id`, COUNT(`follower`) as `subscriptions` FROM `subscriptions` WHERE `model` = \"openvk\\\Web\\\Models\\\Entities\\\Club\" GROUP BY `target` ORDER BY `subscriptions` DESC, `id` LIMIT 30;";
        $entries = DatabaseConnection::i()->getConnection()->query($query);

        foreach($entries as $entry)
            yield (object) [
                "place"         => $entry["place"],
                "club"          => $this->get($entry["id"]),
                "subscriptions" => $entry["subscriptions"],
            ];
    }
    
    use \Nette\SmartObject;
}
