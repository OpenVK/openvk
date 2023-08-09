<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbLogs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class GeodbCity extends RowModel
{
    protected $tableName = "geodb_cities";

    function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    function getCountry(): GeodbCountry
    {
        return (new GeodbCountries)->get($this->getRecord()->country);
    }

    function getName(): string
    {
        return $this->getRecord()->name;
    }

    function getNativeName(): ?string
    {
        return $this->getRecord()->native_name;
    }

    function getCanonicalName(): string
    {
        return $this->getNativeName() ?? $this->getName();
    }

    function getSimplified(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "native_name" => $this->getNativeName()
        ];
    }

    function getRequestSender(): ?User
    {
        return (new Users)->get((int) $this->getRecord()->is_request);
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
