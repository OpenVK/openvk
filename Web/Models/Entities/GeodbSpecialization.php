<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbCities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbEducation;
use openvk\Web\Models\Repositories\GeodbFaculties;
use openvk\Web\Models\Repositories\GeodbLogs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class GeodbSpecialization extends RowModel
{
    protected $tableName = "geodb_specializations";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getName(): string
    {
        return $this->getRecord()->name;
    }

    function getFaculty(): GeodbFaculty
    {
        return (new GeodbFaculties)->get((int) $this->getRecord()->faculty);
    }

    function getUniversity(): GeodbUniversity
    {
        return $this->getFaculty()->getUniversity();
    }

    function getRequestSender(): ?User
    {
        return (new Users)->get((int) $this->getRecord()->is_request);
    }

    function getCity(): ?GeodbCity
    {
        return $this->getUniversity()->getCity();
    }

    function getCountry(): ?GeodbCountry
    {
        return $this->getUniversity()->getCountry();
    }

    function getSimplified(): array
    {
        return [ "id" => $this->getId(), "name" => $this->getName() ];
    }

    function save(User $user, $table): void
    {
        if(is_null($this->record)) {
            $this->record = $table->insert($this->changes);
            (new GeodbLogs)->create($user, $this->tableName, 0, $this->getRecord()->toArray(), $this->changes);
        } else if($this->deleted) {
            (new GeodbLogs)->create($user, $this->tableName, 2, $this, $this->changes);
            $this->record = $table->insert((array) $this->record);
        } else {
            (new GeodbLogs)->create($user, $this->tableName, 1, $this, $this->changes);
            $table->get($this->record->id)->update($this->changes);
            $this->record = $table->get($this->record->id);
        }

        $this->changes = [];
    }
}
