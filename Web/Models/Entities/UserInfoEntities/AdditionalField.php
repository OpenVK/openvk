<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\UserInfoEntities;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection;

class AdditionalField extends RowModel
{
    protected $tableName = "additional_fields";

    const PLACE_CONTACTS  = 0;
    const PLACE_INTERESTS = 1;

    function getOwner(): int
    {
        return (int) $this->getRecord()->owner;
    }

    function getName(bool $tr = true): string
    {
        $orig_name = $this->getRecord()->name;
        $name = $orig_name;
        if($tr && $name[0] === "_")
            $name = tr("custom_field_" . substr($name, 1));

        if(str_contains($name, "custom_field"))
            return $orig_name;

        return $name;
    }

    function getContent(): string
    {
        return $this->getRecord()->text;
    }

    function getPlace(): string
    {
        switch($this->getRecord()->place) {
            case AdditionalField::PLACE_CONTACTS:
                return "contact";
            case AdditionalField::PLACE_INTERESTS:
                return "interest";
        }

        return "contact";
    }

    function isContact(): bool
    {
        return $this->getRecord()->place == AdditionalField::PLACE_CONTACTS;
    }

    function toVkApiStruct(): object
    {
        return (object) [
            "type"   => $this->getRecord()->place,
            "name"   => $this->getName(),
            "text"   => $this->getContent() 
        ];
    }

    static function getById(int $id)
    {
        $ctx = DatabaseConnection::i()->getContext();
        $entry = $ctx->table("additional_fields")->where("id", $id)->fetch();

        if(!$entry)
            return NULL;
        
        return new AdditionalField($entry);
    }

    static function getByOwner(int $owner): \Traversable
    {
        $ctx = DatabaseConnection::i()->getContext();
        $entries = $ctx->table("additional_fields")->where("owner", $owner);

        foreach($entries as $entry) {
            yield new AdditionalField($entry);
        }
    }

    static function getCountByOwner(int $owner): \Traversable
    {
        return DatabaseConnection::i()->getContext()->table("additional_fields")->where("owner", $owner)->count();
    }

    static function resetByOwner(int $owner): bool
    {
        DatabaseConnection::i()->getContext()->table("additional_fields")->where("owner", $owner)->delete();

        return true;
    }
}
