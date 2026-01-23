<?php

declare(strict_types=1);

namespace Chandler\Database;

use Chandler\Database\DatabaseConnection;
use Chandler\Security\Authenticator;
use Chandler\Security\User;
use Nette\Database\Table\Selection;
use Nette\Database\Table\ActiveRow;
use Nette\InvalidStateException as ISE;
use Chandler\Database\Logs;

abstract class DBEntity
{
    protected $record;
    protected $changes;
    protected $deleted;
    private $user;

    protected $tableName;

    public function __construct(?ActiveRow $row = null)
    {
        if (is_null($row)) {
            return;
        }

        $_table = $row->getTable()->getName();
        if ($_table !== $this->tableName) {
            throw new ISE("Invalid data supplied for model: table $_table is not compatible with table" . $this->tableName);
        }

        $this->record = $row;
        $this->user = Authenticator::i()->getUser();
    }

    public function __call(string $fName, array $args)
    {
        if (substr($fName, 0, 3) === "set") {
            $field = mb_strtolower(substr($fName, 3));
            $this->stateChanges($field, $args[0]);
        } else {
            throw new \Error("Call to undefined method " . get_class($this) . "::$fName");
        }
    }

    private function getTable(): Selection
    {
        return DatabaseConnection::i()->getContext()->table($this->tableName);
    }

    protected function getRecord(): ?ActiveRow
    {
        return $this->record;
    }

    protected function stateChanges(string $column, $value): void
    {
        if (!is_null($this->record)) {
            $t = $this->record->{$column};
        } #Test if column exists

        $this->changes[$column] = $value;
    }

    public function getId()
    {
        return $this->getRecord()->id;
    }

    public function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    public function unwrap(): object
    {
        return (object) $this->getRecord()->toArray();
    }

    protected function getContextUserId(): string
    {
        $user = Authenticator::i()->getUser();
        $id = ($user && method_exists($user, 'getId'))
            ? $user->getId()
            : (OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["adminAccount"] ?? 1);

        return (string) $id;
    }

    protected function log(int $type, array $extraChanges = []): void
    {
        (new Logs())->create(
            $this->getContextUserId(),
            $this->getTable()->getName(),
            get_class($this),
            $type,
            $this->record ? $this->record->toArray() : [],
            !empty($extraChanges) ? $extraChanges : $this->changes
        );
    }

    public function delete(bool $softly = true): void
    {
        if (is_null($this->record)) {
            throw new ISE("Can't delete a model, that hasn't been flushed to DB. Have you forgotten to call save() first?");
        }

        $this->log(2);

        if ($softly) {
            $this->record = $this->getTable()->where("id", $this->record->id)->update(["deleted" => true]);
        } else {
            $this->record->delete();
            $this->deleted = true;
        }
    }

    public function undelete(): void
    {
        if (is_null($this->record)) {
            throw new ISE("Can't undelete a model, that hasn't been flushed to DB. Have you forgotten to call save() first?");
        }

        $this->log(3, ["deleted" => false]);

        $this->getTable()->where("id", $this->record->id)->update(["deleted" => false]);
    }

    public function save(?bool $log = false): void
    {
        // Записывать ли логи?
        $shouldLog = $log && $this->getTable()->getName() !== "ChandlerLogs" && (CHANDLER_ROOT_CONF["preferences"]["logs"]["enabled"] ?? false);

        if (is_null($this->record)) {
            $this->record = $this->getTable()->insert($this->changes);

            if ($shouldLog) {
                $this->log(0);
            }
        } else {
            if ($shouldLog) {
                $this->log(1);
            }

            if ($this->deleted) {
                $this->record = $this->getTable()->insert((array) $this->record);
            } else {
                $this->getTable()->get($this->record->id)->update($this->changes);
                $this->record = $this->getTable()->get($this->record->id);
            }
        }

        $this->changes = [];
    }

    public function getTableName(): string
    {
        return $this->getTable()->getName();
    }
}
