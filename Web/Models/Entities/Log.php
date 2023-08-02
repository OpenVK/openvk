<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\GeodbCities;
use openvk\Web\Models\Repositories\GeodbCountries;
use openvk\Web\Models\Repositories\GeodbEducation;
use openvk\Web\Models\Repositories\GeodbSpecializations;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;

class Log extends RowModel
{
    protected $tableName = "logs";

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

    function getTypeRaw(): int
    {
        return $this->getRecord()->type;
    }

    function getType(): string
    {
        return ["добавил", "отредактировал", "удалил", "восстановил"][$this->getTypeRaw()];
    }

    function getTypeNom(): string
    {
        return ["Создание", "Редактирование", "Удаление", "Восстановление"][$this->getTypeRaw()];
    }

    function getObjectType(): string
    {
        return [
            "albums" => "Альбом",
            "groups" => "Сообщество",
            "profiles" => "Профиль",
            "comments" => "Комментарий",
            "ip" => "IP-адрес",
            "posts" => "Запись",
            "tickets" => "Вопрос",
            "tickets_comments" => "Комментарий к тикету",
        ][$this->getRecord()->object_table] ?? $this->getRecord()->object_model;
    }

    function getObjectName(): string
    {
        $object = $this->getObject();
        if (method_exists($object, 'getCanonicalName'))
            return $object->getCanonicalName();
        else return "[#" . $this->getObjectId() . "] " . $this->getObjectType();
    }

    function getLogsText(): string
    {
        return $this->getRecord()->logs_text;
    }

    function getObjectURL(): string
    {
        $object = $this->getObject();
        if (method_exists($object, "getURL") && $this->getObjectTable() !== "videos")
            return $this->getObject()->getURL();
        else
            return "#";
    }

    function getObjectAvatar(): ?string
    {
        $object = $this->getObject();
        if (method_exists($object, 'getAvatarURL'))
            return $object->getAvatarURL("normal");
        else return NULL;
    }

    function getOldValue(): ?array
    {
        return (array) json_decode($this->getRecord()->xdiff_old, true, JSON_UNESCAPED_UNICODE) ?? null;
    }

    function getNewValue(): ?array
    {
        return (array) json_decode($this->getRecord()->xdiff_new, true, JSON_UNESCAPED_UNICODE) ?? null;
    }

    function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->ts);
    }

    function diff($old, $new): array
    {
        $matrix = array();
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));
        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }

    function htmlDiff($old, $new): string
    {
        $ret = '';
        $diff = $this->diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
        foreach ($diff as $k) {
            if (is_array($k))
                $ret .= (!empty($k['d']) ? "<del>" . implode(' ', $k['d']) . "</del> " : '') .
                    (!empty($k['i']) ? "<ins>" . implode(' ', $k['i']) . "</ins> " : '');
            else $ret .= $k . ' ';
        }
        return $ret;
    }

    function getChanges(): array
    {
        $result = $this->getOldValue();
        $_changes = [];

        if ($this->getTypeRaw() === 1) { // edit
            $changes = $this->getNewValue();

            foreach ($changes as $field => $value) {
                $new_value = xdiff_string_patch((string) $result[$field], (string) $value);
                $_changes[$field] = [
                    "field" => $field,
                    "old_value" => $result[$field],
                    "new_value" => strlen($new_value) > 0 ? $new_value : "(empty)",
                    "ts" => $this->getTime(),
                    "diff" => $this->htmlDiff((string) $result[$field], (string) $new_value)
                ];
            }
        } else if ($this->getTypeRaw() === 0) { // create
            foreach ($result as $field => $value) {
                $_changes[$field] = [
                    "field" => $field,
                    "old_value" => $value,
                    "ts" => $this->getTime()
                ];
            }
        } else if ($this->getTypeRaw() === 2) { // delete
            $_changes[] = [
                "field" => "deleted",
                "old_value" => 0,
                "new_value" => 1,
                "ts" => $this->getTime(),
                "diff" => $this->htmlDiff("0", "1")
            ];
        }

        return $_changes;
    }
}
