<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCity;
use openvk\Web\Models\Entities\GeodbFaculty;
use openvk\Web\Models\Entities\GeodbSpecialization;
use openvk\Web\Models\Entities\GeodbUniversity;

class GeodbSpecializations
{
    private $context;
    private $specializations;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->specializations  = $this->context->table("geodb_specializations");
    }

    private function toGeodbSpecialization(?ActiveRow $ar): ?GeodbSpecialization
    {
        return is_null($ar) ? NULL : new GeodbSpecialization($ar);
    }

    function get(int $id): ?GeodbSpecialization
    {
        return $this->toGeodbSpecialization($this->specializations->get($id));
    }

    function getList(?int $fid = NULL, ?bool $needDeleted = false, ?bool $simplified = false, ?bool $needRequests = false): \Traversable
    {
        $filter[($fid < 0 ? "country" : "faculty")] = ($fid < 0 ? ($fid * -1) : $fid);
        $filter["deleted"] = $needDeleted;

        foreach ($this->specializations->where($filter) as $specialization) {
            $response = new GeodbSpecialization($specialization);
            if ($needRequests && (!$response->getRequestSender())) continue;
            if (!$needRequests && ($response->getRequestSender())) continue;
            if ($simplified) $response = $response->getSimplified();

            yield $response;
        }
    }

    function getRequestsCount(): int
    {
        return $this->specializations->where("is_request != 0 && deleted = 0")->count();
    }

    function getTable()
    {
        return $this->specializations;
    }
}
