<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCity;
use openvk\Web\Models\Entities\User;

class GeodbCities
{
    private $context;
    private $cities;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->cities  = $this->context->table("geodb_cities");
    }

    private function toGeodbCity(?ActiveRow $ar): ?GeodbCity
    {
        return is_null($ar) ? NULL : new GeodbCity($ar);
    }

    function get(int $id): ?GeodbCity
    {
        return $this->toGeodbCity($this->cities->get($id));
    }

    function getList(int $cid, ?bool $needDeleted = false, ?bool $simplified = false, ?bool $needRequests = false): \Traversable
    {
        $filter = ["country" => $cid];
        $filter["deleted"] = $needDeleted;

        foreach ($this->cities->where($filter) as $city) {
            $response = new GeodbCity($city);
            if ($needRequests && (!$response->getRequestSender())) continue;
            if (!$needRequests && ($response->getRequestSender())) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }

    function find($q): ?GeodbCity
    {
        return $this->toGeodbCity($this->context->table("geodb_cities")->where("deleted = 0 AND is_request = 0 AND (id LIKE ? OR name LIKE ? OR native_name LIKE ?)", $q, $q, $q)->fetch());
    }

    function getRequestsCount(): int
    {
        return $this->cities->where("is_request != 0 && deleted = 0")->count();
    }

    function getTable()
    {
        return $this->cities;
    }
}
