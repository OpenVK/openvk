<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\UserInfoEntities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection;

class AdditionalField extends RowModel
{
    protected $tableName = "additional_fields";

    public const PLACE_CONTACTS  = 0;
    public const PLACE_INTERESTS = 1;

    public function getOwner(): int
    {
        return (int) $this->getRecord()->owner;
    }

    public function getName(bool $tr = true): string
    {
        $orig_name = $this->getRecord()->name;
        $name = $orig_name;
        if ($tr && $name[0] === "_") {
            $name = tr("custom_field_" . substr($name, 1));
        }

        if (str_contains($name, "custom_field")) {
            return $orig_name;
        }

        return $name;
    }

    public function getContent(): string
    {
        return $this->getRecord()->text;
    }

    public function getPlace(): string
    {
        switch ($this->getRecord()->place) {
            case AdditionalField::PLACE_CONTACTS:
                return "contact";
            case AdditionalField::PLACE_INTERESTS:
                return "interest";
        }

        return "contact";
    }

    public function isContact(): bool
    {
        return $this->getRecord()->place == AdditionalField::PLACE_CONTACTS;
    }

    public function toVkApiStruct(): object
    {
        return (object) [
            "type"   => $this->getRecord()->place,
            "name"   => $this->getName(),
            "text"   => $this->getContent(),
        ];
    }

    public static function getById(int $id)
    {
        $ctx = DatabaseConnection::i()->getContext();
        $entry = $ctx->table("additional_fields")->where("id", $id)->fetch();

        if (!$entry) {
            return null;
        }

        return new AdditionalField($entry);
    }

    public static function getByOwner(int $owner): \Traversable
    {
        $ctx = DatabaseConnection::i()->getContext();
        $entries = $ctx->table("additional_fields")->where("owner", $owner);

        foreach ($entries as $entry) {
            yield new AdditionalField($entry);
        }
    }

    public static function getCountByOwner(int $owner): \Traversable
    {
        return DatabaseConnection::i()->getContext()->table("additional_fields")->where("owner", $owner)->count('*');
    }

    public static function resetByOwner(int $owner): bool
    {
        DatabaseConnection::i()->getContext()->table("additional_fields")->where("owner", $owner)->delete();

        return true;
    }
}
