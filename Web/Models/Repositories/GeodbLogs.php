<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCountry;
use openvk\Web\Models\Entities\GeodbEditor;
use openvk\Web\Models\Entities\GeodbLog;
use openvk\Web\Models\Entities\User;

class GeodbLogs
{
    private $context;
    private $logs;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->logs  = $this->context->table("geodb_logs");
    }

    private function toGeodbLog(?ActiveRow $ar): ?GeodbLog
    {
        return is_null($ar) ? NULL : new GeodbLog($ar);
    }

    function get(int $id): ?GeodbLog
    {
        return $this->toGeodbLog($this->logs->get($id));
    }

    function getList(int $uid): \Traversable
    {
        $filter = [];
        if ($uid) $filter["user"] = $uid;

        foreach ($this->logs->where($filter)->order("id DESC") as $log)
            yield new GeodbLog($log);
    }

    function create(User $user, string $table, int $type, $object, $changes): void
    {
        $model = "openvk\\Web\\Models\\Entities\\" . [
            "geodb_countries" => "GeodbCountry",
            "geodb_cities" => "GeodbCity",
            "geodb_schools" => "GeodbSchool",
            "geodb_universities" => "GeodbUniversity",
            "geodb_faculties" => "GeodbFaculty",
            "geodb_specializations" => "GeodbSpecialization",
            "geodb_editors" => "GeodbEditor",
        ][$table];
        $fields = [
            "name" => "Название",
            "native_name" => "Родное название",
            "code" => "Код",
            "flag" => "Флаг",
            "country" => "Страна",
            "city" => "Город",
            "university" => "Университет",
            "faculty" => "Факультет",
            "deleted" => "Удалено",
            "uid" => "ID пользователя",
            "edu" => "Образование",
            "cities" => "Города"
        ];

        $fobject = (is_array($object) ? $object : $object->unwrap());

        $text = "";
        foreach ($fobject as $field => $value) {
            if ($changes[$field] === NULL) continue;
            if (in_array($field, ["id", "is_log"])) continue;
            if ($changes[$field] == $value && !is_array($object)) continue;

            if (is_array($object)) {
                $text .= "<b>" . ($fields[$field] ?? $field) . "</b>: $value<br/>";
            } else {
                $text .= "<b>" . ($fields[$field] ?? $field) . "</b>: $value → $changes[$field]<br/>";
            }
        }

        $log = new GeodbLog;
        $log->setUser($user->getId());
        $log->setType($type);
        $log->setObject_Table($table);
        $log->setObject_Model($model);
        $log->setObject_Id(is_array($object) ? $object["id"] : $object->getId());
        $log->setLogs_Text($text);
        $log->save();
    }
}
