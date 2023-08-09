<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCountry;

class GeodbCountries
{
    private $context;
    private $countries;

    function __construct()
    {
        $this->context    = DatabaseConnection::i()->getContext();
        $this->countries  = $this->context->table("geodb_countries");
    }

    private function toGeodbCountry(?ActiveRow $ar): ?GeodbCountry
    {
        return is_null($ar) ? NULL : new GeodbCountry($ar);
    }

    function get(int $id): ?GeodbCountry
    {
        return $this->toGeodbCountry($this->countries->get($id));
    }

    function getList(?bool $simplified = false, ?bool $needDeleted = false): \Traversable
    {
        foreach ($this->countries as $country) {
            $response = new GeodbCountry($country);
            if (!$needDeleted && $response->isDeleted()) continue;
            if ($needDeleted && !$response->isDeleted()) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }

    function getByCode($code): ?GeodbCountry
    {
        return $this->toGeodbCountry($this->countries->where("code", $code)->fetch());
    }

    function find($q): ?GeodbCountry
    {
        return $this->toGeodbCountry($this->context->table("geodb_countries")->where("deleted = 0 AND is_request = 0 AND (id LIKE ? OR name LIKE ? OR native_name LIKE ?)", $q, $q, $q)->fetch());
    }

    function getTable()
    {
        return $this->countries;
    }
}
