<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\{Club, Manager};
use openvk\Web\Models\Repositories\{Aliases, Users};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Clubs
{
    use \Nette\SmartObject;
    private $context;
    private $clubs;
    private $coadmins;

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->clubs    = $this->context->table("groups");
        $this->coadmins = $this->context->table("group_coadmins");
    }

    private function toClub(?ActiveRow $ar): ?Club
    {
        return is_null($ar) ? null : new Club($ar);
    }

    public function getByShortURL(string $url): ?Club
    {
        $shortcode = $this->toClub($this->clubs->where("shortcode", $url)->fetch());

        if ($shortcode) {
            return $shortcode;
        }

        $alias = (new Aliases())->getByShortcode($url);

        if (!$alias) {
            return null;
        }
        if ($alias->getType() !== "club") {
            return null;
        }

        return $alias->getClub();
    }

    public function get(int $id): ?Club
    {
        return $this->toClub($this->clubs->get($id));
    }

    public function getByIds(array $ids = []): array
    {
        $clubs = $this->clubs->select('*')->where('id IN (?)', $ids);
        $clubs_array = [];

        foreach ($clubs as $club) {
            $clubs_array[] =  $this->toClub($club);
        }

        return $clubs_array;
    }

    public function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false], int $page = 1, ?int $perPage = null): \Traversable
    {
        $query = "%$query%";
        $result = $this->clubs;
        $order_str = 'id';

        switch ($order['type']) {
            case 'id':
                $order_str = 'id ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        $result = $result->where("name LIKE ? OR about LIKE ?", $query, $query);

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Club", $result);
    }

    public function getCount(): int
    {
        return (clone $this->clubs)->count('*');
    }

    public function getPopularClubs(): ?\Traversable
    {
        // TODO rewrite

        /*
        $query   = "SELECT ROW_NUMBER() OVER (ORDER BY `subscriptions` DESC) as `place`, `target` as `id`, COUNT(`follower`) as `subscriptions` FROM `subscriptions` WHERE `model` = \"openvk\\\Web\\\Models\\\Entities\\\Club\" GROUP BY `target` ORDER BY `subscriptions` DESC, `id` LIMIT 30;";
        $entries = DatabaseConnection::i()->getConnection()->query($query);

        foreach($entries as $entry)
            yield (object) [
                "place"         => $entry["place"],
                "club"          => $this->get($entry["id"]),
                "subscriptions" => $entry["subscriptions"],
            ];
        */
        trigger_error("Clubs::getPopularClubs() is currently commented out and returns null", E_USER_WARNING);
        return null;
    }

    public function getWriteableClubs(int $id): \Traversable
    {
        $result    = $this->clubs->where("owner", $id);
        $coadmins  = $this->coadmins->where("user", $id);

        foreach ($result as $entry) {
            yield new Club($entry);
        }

        foreach ($coadmins as $coadmin) {
            $cl = new Manager($coadmin);
            yield $cl->getClub();
        }
    }

    public function getWriteableClubsCount(int $id): int
    {
        return sizeof($this->clubs->where("owner", $id)) + sizeof($this->coadmins->where("user", $id));
    }
}
