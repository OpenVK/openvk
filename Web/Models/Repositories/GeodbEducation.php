<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCity;
use openvk\Web\Models\Entities\GeodbSchool;
use openvk\Web\Models\Entities\GeodbUniversity;

class GeodbEducation
{
    private $context;
    private $schools;
    private $universities;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->schools  = $this->context->table("geodb_schools");
        $this->universities  = $this->context->table("geodb_universities");
    }

    private function toGeodbSchool(?ActiveRow $ar): ?GeodbSchool
    {
        return is_null($ar) ? NULL : new GeodbSchool($ar);
    }

    private function toGeodbUniversity(?ActiveRow $ar): ?GeodbUniversity
    {
        return is_null($ar) ? NULL : new GeodbUniversity($ar);
    }

    function getSchool(int $id): ?GeodbSchool
    {
        return $this->toGeodbSchool($this->schools->get($id));
    }

    function getUniversity(int $id): ?GeodbUniversity
    {
        return $this->toGeodbUniversity($this->universities->get($id));
    }

    function getSchools(int $country_id, ?int $city_id = NULL, ?bool $needDeleted = false, ?bool $simplified = false, ?bool $needRequests = false): \Traversable
    {
        $filter = ["country" => $country_id];
        if ($city_id) $filter["city"] = $city_id;
        $filter["deleted"] = $needDeleted;

        foreach ($this->schools->where($filter) as $school) {
            $response = new GeodbSchool($school);
            if ($needRequests && (!$response->getRequestSender())) continue;
            if (!$needRequests && ($response->getRequestSender())) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }


    function getUniversities(int $country_id, ?int $city_id = NULL, ?bool $needDeleted = false, ?bool $simplified = false, ?bool $needRequests = false): \Traversable
    {
        $filter = ["country" => $country_id];
        if ($city_id) $filter["city"] = $city_id;
        $filter["deleted"] = $needDeleted;

        foreach ($this->universities->where($filter) as $university) {
            $response = new GeodbUniversity($university);
            if ($needRequests && (!$response->getRequestSender())) continue;
            if (!$needRequests && ($response->getRequestSender())) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }

    function getSchoolsRequestsCount(): int
    {
        return $this->schools->where("is_request != 0 && deleted = 0")->count();
    }

    function getUniversitiesRequestsCount(): int
    {
        return $this->universities->where("is_request != 0 && deleted = 0")->count();
    }

    function getTable(string $view) {
        return ($view === "universities") ? $this->universities : $this->schools;
    }
}
