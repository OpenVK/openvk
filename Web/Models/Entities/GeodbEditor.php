<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbLogs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class GeodbEditor extends RowModel
{
    protected $tableName = "geodb_editors";

    function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    function getUser(): User
    {
        return (new Users)->get((int) $this->getRecord()->uid);
    }

    function getCountry(): ?GeodbCountry
    {
        return (new GeodbCountries)->get((int) $this->getRecord()->country);
    }

    function canEditEducation(): bool
    {
        return (bool) $this->getRecord()->edu;
    }

    function canEditCities(): bool
    {
        return (bool) $this->getRecord()->cities;
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

    function getName(): string
    {
        return $this->getUser()->getCanonicalName() . " (" . ($this->getCountry()->getCanonicalName()) . ")";
    }
}
