<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbCities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbEducation;
use openvk\Web\Models\Repositories\GeodbLogs;
use openvk\Web\Models\Repositories\GeodbSpecializations;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class GeodbFaculty extends RowModel
{
    protected $tableName = "geodb_faculties";

    function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    function getUniversity(): GeodbUniversity
    {
        return (new GeodbEducation)->getUniversity((int) $this->getRecord()->university);
    }

    function getName(): string
    {
        return $this->getRecord()->name;
    }

    function getSimplified(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName()
        ];
    }

    function getSpecializations(?bool $needDeleted = false, ?bool $simplified = false): \Traversable
    {
        return (new GeodbSpecializations)->getList($this->getId(), $needDeleted, $simplified);
    }

    function getRequestSender(): ?User
    {
        return (new Users)->get((int) $this->getRecord()->is_request);
    }

    function getCountry(): ?GeodbCountry
    {
        return $this->getUniversity()->getCountry();
    }

    function getCity(): ?GeodbCity
    {
        return $this->getUniversity()->getCity();
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
