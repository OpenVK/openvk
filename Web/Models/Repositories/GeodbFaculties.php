<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCity;
use openvk\Web\Models\Entities\GeodbFaculty;
use openvk\Web\Models\Entities\GeodbUniversity;

class GeodbFaculties
{
    private $context;
    private $faculties;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->faculties  = $this->context->table("geodb_faculties");
    }

    private function toGeodbFaculty(?ActiveRow $ar): ?GeodbFaculty
    {
        return is_null($ar) ? NULL : new GeodbFaculty($ar);
    }

    function get(int $id): ?GeodbFaculty
    {
        return $this->toGeodbFaculty($this->faculties->get($id));
    }

    function getList(int $uid, ?bool $needDeleted = false, ?bool $simplified = false, ?bool $needRequests = false): \Traversable
    {
        $filter = ["university" => $uid];
        $filter["deleted"] = $needDeleted;

        foreach ($this->faculties->where($filter) as $city) {
            $response = new GeodbFaculty($city);
            if ($needRequests && (!$response->getRequestSender())) continue;
            if (!$needRequests && ($response->getRequestSender())) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }

    function getRequestsCount(): int
    {
        return $this->faculties->where("is_request != 0 && deleted = 0")->count();
    }

    function getTable()
    {
        return $this->faculties;
    }
}
