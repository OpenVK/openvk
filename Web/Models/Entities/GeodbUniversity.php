<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbFaculties;

class GeodbUniversity extends GeodbSchool
{
    protected $tableName = "geodb_universities";

    function getFaculties(?bool $need_deleted = false, ?bool $simplified = false): \Traversable
    {
        $faculties = (new GeodbFaculties)->getList($this->getId(), $need_deleted);
        foreach ($faculties as $faculty) {
            $response = $faculty;
            if ($simplified) $response = $response->getSimplified();
            yield $response;
        }
    }
}
