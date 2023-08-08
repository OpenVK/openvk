<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\Country;
use openvk\Web\Models\Entities\Editor;
use openvk\Web\Models\Entities\Log;
use openvk\Web\Models\Entities\User;

class Logs
{
    private $context;
    private $logs;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->logs  = $this->context->table("logs");
    }

    private function toLog(?ActiveRow $ar): ?Log
    {
        return is_null($ar) ? NULL : new Log($ar);
    }

    function get(int $id): ?Log
    {
        return $this->toLog($this->logs->get($id));
    }

    function create(int $user, string $table, string $model, int $type, $object, $changes, ?string $ip = NULL, ?string $useragent = NULL): void
    {
        if (OPENVK_ROOT_CONF["openvk"]["preferences"]["logs"] === true) {
            $fobject = (is_array($object) ? $object : $object->unwrap());
            $nobject = [];
            $_changes = [];

            if ($type === 1) {
                foreach ($changes as $field => $value) {
                    $nobject[$field] = $fobject[$field];
                }

                foreach (array_diff_assoc($nobject, $changes) as $field => $value) {
                    if (str_starts_with($field, "rate_limit")) continue;
                    if ($field === "online") continue;
                    $_changes[$field] = xdiff_string_diff((string)$nobject[$field], (string)$changes[$field]);
                }

                if (count($_changes) === 0) return;
            } else if ($type === 0) { // if new
                $nobject = $fobject;
                foreach ($fobject as $field => $value) {
                    $_changes[$field] = xdiff_string_diff("", (string)$value);
                }
            } else if ($type === 2 || $type === 3) { // if deleting or restoring
                $_changes["deleted"] = (int)($type === 2);
            }

            $log = new Log;
            $log->setUser($user);
            $log->setType($type);
            $log->setObject_Table($table);
            $log->setObject_Model($model);
            $log->setObject_Id(is_array($object) ? $object["id"] : $object->getId());
            $log->setXdiff_Old(json_encode($nobject));
            $log->setXdiff_New(json_encode($_changes));
            $log->setTs(time());
            $log->setIp($ip ?? CurrentUser::i()->getIP());
            $log->setUserAgent($useragent ?? CurrentUser::i()->getUserAgent());
            $log->save();
        }
    }

    function find(string $query, array $pars = [], string $sort = "id DESC", int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $query  = "%$query%";
        $result = $this->logs->where("id LIKE ? OR object_table LIKE ?", $query, $query);

        return new Util\EntityStream("Log", $result->order($sort));
    }

    function search($filter): \Traversable
    {
        foreach ($this->logs->where($filter)->order("id DESC") as $log)
            yield new Log($log);
    }

    function getTypes(): array
    {
        $types = [];
        foreach ($this->context->query("SELECT DISTINCT(`object_model`) AS `object_model` FROM `logs`")->fetchAll() as $type)
            $types[] = str_replace("openvk\\Web\\Models\\Entities\\", "", $type->object_model);

        return $types;
    }
}
