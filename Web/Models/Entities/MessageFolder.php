<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;

class MessageFolder extends RowModel
{
    protected $tableName = "im_message_folders";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getOwnerId(): int
    {
        return $this->getRecord()->owner;
    }

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getType(): string
    {
        return $this->getRecord()->type;
    }

    public function getPosition(): int
    {
        return $this->getRecord()->position;
    }

    public function getPeerIds(): array
    {
        $rows = DatabaseConnection::i()->getContext()
            ->table("im_message_folder_peers")
            ->where("folder", $this->getId());

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->peer;
        }

        return $ids;
    }

    public function toVkApiStruct(): object
    {
        return (object) [
            "id"                => $this->getId(),
            "type"              => $this->getType(),
            "name"              => $this->getName(),
            "included_peer_ids" => $this->getPeerIds(),
            "included_lists"    => [],
            "flags"             => 0,
            "random_id"         => 0,
        ];
    }
}
