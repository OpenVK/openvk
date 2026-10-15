<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Videos, Applications, Audios, Documents};
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;

class Catalogue extends RowModel
{
    protected $tableName= "catalogues";
    private $user;

    public const SUBTYPE_NONE = 0;
    public const SUBTYPE_CUSTOM_FEED = 1;

    public function getSubtype(): int
    {
        return $this->getRecord()->subtype ?? $this->changes["subtype"] ?? 0;
    }
    public function getTitle(): string
    {
        return $this->getRecord()->title ?? $this->changes["title"];
    }
    public function isCustomFeed(): bool 
    {
        return $this->getSubtype() == 1;
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        $owner = $this->getRecord()->owner ?? $this->changes["owner"];
        $set = $user->getRealId() === $owner;

        $editableTo = explode(",", $this->getRecord()->editable_to ?? "");

        if (!$set) {
            return in_array($user->getRealId(), $editableTo);
        }

        return $set;
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        $owner = $this->getRecord()->owner ?? $this->changes["owner"];
        $isPrivate = $this->getRecord()->private ?? $this->changes["private"];
        $visibleTo = explode(",", ($this->getRecord()->visible_to ?? $this->changes["visible_to"]) ?? "");
        $editableTo = explode(",", ($this->getRecord()->editable_to ?? $this->changes["editable_to"]) ?? "");
        $set = $user->getRealId() === $owner;

        if (!$user) {
            return false;
        }

        if ($set) {
            return true;
        }

        return $isPrivate == 0 || in_array($user->getRealId(), $visibleTo) || in_array($user->getRealId(), $editableTo);
    }

    public function clear()
    {
        DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_relations")
        ->where("catalogue", $this->getId())
        ->delete();
    }

    public function addItem(RowModel $item): void
    {
        $modelCode = modelCode($item);
        $order = DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_relations")
        ->where("catalogue", $this->getId())
        ->where("item", $item->getId())
        ->count();

        $sub = DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_relations")
        ->insert([
            "catalogue" => $this->getId(),
            "model"     => $modelCode,
            "item"      => $item->getId(),
            "ord"       => $order + 1,
        ]);
    }

    public function removeItem(RowModel $item): bool
    {
        $modelCode = modelCode($item);
        $sub = DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_relations")
        ->where([
            "catalogue" => $this->getId(),
            "model"     => $modelCode,
            "item"      => $item->getId(),
            "ord"       => $order,
        ]);
        $sub->delete();

        return true;
    }

    public function isImported(?RowModel $to = null): bool
    {
        if (!$to) {
            return false;
        }

        $pars = [
            "catalogue" => $this->getId(),
            "owner"     => $to->getRealId(),
        ];

        return DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_imports")
        ->where($pars)
        ->count() > 0;
    }

    public function toggleImport(RowModel $to, bool $state = true): void
    {
        $vals = DatabaseConnection::i()
        ->getContext()
        ->table("catalogues_imports")
        ->where("owner", $to->getRealId())
        ->count();

        $pars = [
            "catalogue" => $this->getId(),
            "owner"     => $to->getRealId(),
            "ord"       => $vals + 1,
        ];

        if ($state == true) {
            DatabaseConnection::i()
                ->getContext()
                ->table("catalogues_imports")
                ->insert($pars);
        } else {
            DatabaseConnection::i()
                ->getContext()
                ->table("catalogues_imports")
                ->where($pars)
                ->delete();
        }
    }

    public function fetchClassic(?User $user, int $offset = 0, ?int $limit = null, bool $rev = false, ?array $modelCodes = null, bool $keepClosed = true): \Traversable
    {
        $items = DatabaseConnection::i()->getContext()->table("catalogues_relations")->where("catalogue", $this->getId())->order("ord DESC");

        if ($modelCodes) {
            $items = $items->where("model IN (?)", $modelCodes);
        }

        foreach ($items as $item) {
            $modelCode = modelCode($item->model);
            $itemId = $item->item;

            $repoStr = str_replace("Entities", "Repositories", "\\" . $modelCode . "s");
            $repo = new $repoStr();

            $res = $repo->get($itemId);
            if ($keepClosed) {
                if ($res->isClosed()) {
                    continue;
                }
            }

            yield $res;
        }
    }

    public function fetch(?User $user, int $page = 1, ?int $perPage = null, bool $rev = false, ?array $modelCodes = null, bool $keepClosed = true): \Traversable
    {
        $page      = max(1, $page);
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;

        return $this->fetchClassic($user, $perPage * ($page - 1), $perPage, $rev, $modelCodes);
    }

    public function isolate()
    {
        DatabaseConnection::i()
            ->getContext()
            ->table("catalogues_imports")
            ->where("catalogue", $this->getId())
            ->delete();
    }

    public function toVkApiStructOfFeed(?User $user): object
    {
        if (!$this->isCustomFeed()) {
            return (object) [];
        }

        $payload = (object) [
            "id" => $this->getId(),
            "title" => $this->getRecord()->title,
            "no_reposts" => 0,
            "source_ids" => [],
        ];

        foreach ($this->fetch($user, 1, null, false, [5, 18]) as $item) {
            $payload->source_ids[] = $item->getRealId();
        }

        return $payload;
    }

    public static function getImported(?User $user, ?int $subtype = null)
    {
        $sub = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues_imports")
            ->select("catalogue")
            ->where("owner", $user->getRealId())
            ->order("ord DESC");

        $ids = [];

        foreach ($sub as $item) {
            $ids[] = $item->catalogue;
        }

        $select = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues")
            ->where("id IN (?)", $ids)
            ->where("deleted", 0)
            ->order("id ASC");

        if ($subtype) {
            $select = $select->where("subtype", $subtype);
        }

        foreach ($select as $item) {
            yield new Catalogue($item);
        }
    }

    public static function getOwn(?User $user, ?int $subtype = null)
    {
        $select = DatabaseConnection::i()
            ->getContext()
            ->table("catalogues")
            ->where("owner", $user->getRealId())
            ->where("deleted", 0)
            ->order("id ASC");

        if ($subtype) {
            $select = $select->where("subtype", $subtype);
        }

        foreach ($select as $item) {
            yield new Catalogue($item);
        }
    }
}
