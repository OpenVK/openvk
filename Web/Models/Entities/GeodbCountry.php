<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\GeodbCities;
use openvk\Web\Models\Repositories\GeodbEducation;
use openvk\Web\Models\Repositories\GeodbLogs;
use openvk\Web\Models\Repositories\GeodbRights;
use openvk\Web\Models\RowModel;

class GeodbCountry extends RowModel
{
    protected $tableName = "geodb_countries";

    function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    function getCode(): string
    {
        return $this->getRecord()->code;
    }

    function getFlagURL(): string
    {
        return "/assets/packages/static/openvk/img/flags/" . $this->getRecord()->flag . ".gif";
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

    function getEditors(): \Traversable
    {
        return (new GeodbRights)->getList(NULL, $this->getId());
    }

    function getUserRights(int $user_id): ?GeodbEditor
    {
        return (new GeodbRights)->getCountryPermission($user_id, $this->getId());
    }

    function isUserCanEditEducation(int $user_id): bool
    {
        $rights = $this->getUserRights($user_id);
        if (!$rights) return false;

        return $rights->canEditEducation();
    }

    function isUserCanEditCities(int $user_id): bool
    {
        $rights = $this->getUserRights($user_id);
        if (!$rights) return false;

        return $rights->canEditCities();
    }

    function getSimplified(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "native_name" => $this->getNativeName()
        ];
    }

    function getCitiesCount(): int
    {
        return count(iterator_to_array((new GeodbCities)->getList($this->getId())));
    }

    function getSchoolsCount(): int
    {
        return count(iterator_to_array((new GeodbEducation)->getSchools($this->getId())));
    }

    function getUniversitiesCount(): int
    {
        return count(iterator_to_array((new GeodbEducation)->getUniversities($this->getId())));
    }

    function getFlagCode(): string
    {
        return $this->getRecord()->flag;
    }

    function save(User $user, $table, ?bool $new = false): void
    {
        if(is_null($this->record)) {
            $this->record = $table->insert($this->changes);
            (new GeodbLogs)->create($user, $this->tableName, 0, $this->getRecord()->toArray(), $this->changes);
        } else if($this->deleted) {
            (new GeodbLogs)->create($user, $this->tableName, 2, $this, $this->changes, $new);
            $this->record = $table->insert((array) $this->record);
        } else {
            (new GeodbLogs)->create($user, $this->tableName, 1, $this, $this->changes, $new);
            $table->get($this->record->id)->update($this->changes);
            $this->record = $table->get($this->record->id);
        }

        $this->changes = [];
    }
}
