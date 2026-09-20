<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\MessageFolder;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class MessageFolders
{
    private $context;
    private $folders;
    private $peers;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->folders = $this->context->table("im_message_folders");
        $this->peers   = $this->context->table("im_message_folder_peers");
    }

    private function toFolder(?ActiveRow $ar): ?MessageFolder
    {
        return is_null($ar) ? null : new MessageFolder($ar);
    }

    public function get(int $id): ?MessageFolder
    {
        return $this->toFolder($this->folders->get($id));
    }

    public function getByOwner(int $owner): \Traversable
    {
        foreach ($this->folders->where("owner", $owner)->order("position ASC, id ASC") as $row) {
            yield new MessageFolder($row);
        }
    }

    public function getCountByOwner(int $owner): int
    {
        return $this->folders->where("owner", $owner)->count();
    }

    public function create(int $owner, string $name, string $type, array $peerIds): MessageFolder
    {
        $position = $this->folders->where("owner", $owner)->count();
        $row = $this->folders->insert([
            "owner"    => $owner,
            "name"     => $name,
            "type"     => $type !== "" ? $type : "custom",
            "position" => $position,
            "created"  => time(),
        ]);

        $folder = new MessageFolder($row);
        $this->addPeers($folder->getId(), $peerIds);

        return $folder;
    }

    public function addPeers(int $folderId, array $peerIds): void
    {
        foreach ($peerIds as $peer) {
            $peer = (int) $peer;
            if ($peer === 0) {
                continue;
            }
            if ($this->peers->where(["folder" => $folderId, "peer" => $peer])->count() === 0) {
                $this->peers->insert(["folder" => $folderId, "peer" => $peer]);
            }
        }
    }

    public function removePeers(int $folderId, array $peerIds): void
    {
        foreach ($peerIds as $peer) {
            $this->peers->where(["folder" => $folderId, "peer" => (int) $peer])->delete();
        }
    }

    public function rename(int $folderId, string $name): void
    {
        $this->folders->where("id", $folderId)->update(["name" => $name]);
    }

    public function delete(int $folderId): void
    {
        $this->peers->where("folder", $folderId)->delete();
        $this->folders->where("id", $folderId)->delete();
    }

    public function reorder(int $owner, array $folderIds): void
    {
        $position = 0;
        foreach ($folderIds as $folderId) {
            $this->folders->where(["id" => (int) $folderId, "owner" => $owner])->update(["position" => $position]);
            $position++;
        }
    }
}
