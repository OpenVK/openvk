<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\GeodbCities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbEducation;
use openvk\Web\Models\Repositories\GeodbSpecializations;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class GeodbLog extends RowModel
{
    protected $tableName = "geodb_logs";

    function getId(): int
    {
        return (int) $this->getRecord()->id;
    }

    function getUser(): ?User
    {
        return (new Users)->get((int) $this->getRecord()->user);
    }

    function getObjectTable(): string
    {
        return $this->getRecord()->object_table;
    }

    function getObjectId(): int
    {
        return $this->getRecord()->object_id;
    }

    function getObject()
    {
        $model = $this->getRecord()->object_model;
        return new $model(DatabaseConnection::i()->getContext()->table($this->getObjectTable())->get($this->getObjectId()));
    }

    function getType(): string
    {
        return ["добавил", "отредактировал", "удалил", "восстановил"][$this->getRecord()->type];
    }

    function getObjectType(): string
    {
        return [
            "geodb_countries" => "страну",
            "geodb_cities" => "город",
            "geodb_schools" => "школу",
            "geodb_universities" => "университет",
            "geodb_faculties" => "факультет",
            "geodb_specializations" => "специальность",
            "geodb_editors" => "редактора базы",
        ][$this->getRecord()->object_table];
    }

    function getObjectName(): string
    {
        return in_array($this->getObjectTable(), ["geodb_cities", "geodb_countries"]) ? $this->getObject()->getNativeName() : $this->getObject()->getName();
    }

    function getLogsText(): string
    {
        return $this->getRecord()->logs_text;
    }

    function getObjectURL(): string
    {
        switch ($this->getObjectTable()) {
            case "geodb_countries": return "/editdb?act=country&id=" . $this->getObjectId();
            case "geodb_cities": return "/editdb?act=city&id=" . $this->getObjectId();
            case "geodb_schools": return "/editdb?act=school&id=" . $this->getObjectId();
            case "geodb_universities": return "/editdb?act=university&id=" . $this->getObjectId();
            case "geodb_editors": return $this->getObject()->getUser()->getURL();
            case "geodb_faculties" || "geodb_specializations": return "/editdb?act=university&id=" . $this->getObject()->getUniversity()->getId();
            default: return "/err404.php?id=" . $this->getObjectId();
        }
    }
}
