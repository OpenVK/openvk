<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Messages\{Sticker, StickerPack};
use Nette\Database\Table\ActiveRow;

class Stickers
{
    private $context;

    /* aggressive sql caching */
    private static $cache = [];
    private static $cachePack = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
    }

    private function getPacksTable()
    {
        return $this->context->table("stickerpacks");
    }

    private function getStickersTable()
    {
        return $this->context->table("stickers");
    }

    private function toSticker(?ActiveRow $ar): ?Sticker
    {
        return is_null($ar) ? null : new Sticker($ar);
    }

    private function toStickerPack(?ActiveRow $ar): ?StickerPack
    {
        return is_null($ar) ? null : new StickerPack($ar);
    }

    public function get(int $id): ?Sticker
    {
        return self::$cache[$id] ??= $this->toSticker($this->getStickersTable()->where("id", $id)->fetch());
    }

    public function getSticker(int $id): ?Sticker
    {
        return $this->get($id);
    }

    public function getPack(int $id): ?StickerPack
    {
        if (isset(self::$cachePack[$id])) {
            return self::$cachePack[$id];
        }

        $row = $this->getPacksTable()->where("id", $id)->fetch();
        return self::$cachePack[$id] = $this->toStickerPack($row);
    }

    public function clearCache(?int $packId = null): void
    {
        if ($packId !== null) {
            unset(self::$cachePack[$packId]);
        } else {
            self::$cachePack = [];
            self::$cache = [];
        }
    }

    public function getPackBySlug(string $slug): ?StickerPack
    {
        $row = $this->getPacksTable()->where("deleted", false)->where("slug", $slug)->fetch();
        return $this->toStickerPack($row);
    }

    public function getPacks(int $page, ?int $perPage = null, &$count = null, ?string $section = null): \Traversable
    {
        $packs = $this->getPacksTable()
            ->where("deleted", false)
            ->where("unlisted", false);

        if ($section === "free") {
            $packs = $packs->where("price", 0);
        }

        $count = $packs->count("*");
        $packs = $packs->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function getMyPacks(User $user, int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $purchases = $this->context->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("purchased", 1);

        $count = $purchases->count("*");
        $purchases = $purchases->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($purchases as $purchase) {
            $packRec = $this->getPacksTable()->where("id", $purchase->stickerpack)->fetch();
            if ($packRec && !$packRec->deleted) {
                yield new StickerPack($packRec);
            }
        }
    }

    public function getMyPacksCount(User $user): int
    {
        return $this->context->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("purchased", 1)
            ->count("*");
    }

    public function getInstalledPackIds(User $user): array
    {
        $purchases = $this->context->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("purchased", 1)
            ->fetchPairs("stickerpack", "stickerpack");

        return array_map("intval", array_keys($purchases));
    }

    public function getPurchasedPackIds(User $user): array
    {
        return $this->getInstalledPackIds($user);
    }

    public function getBoughtPackIds(User $user): array
    {
        $purchases = $this->context->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("purchased", [1, 2])
            ->fetchPairs("stickerpack", "stickerpack");

        $boughtIds = array_map("intval", array_keys($purchases));

        $owned = $this->context->table("stickerpacks")
            ->where("owner_id", $user->getId())
            ->where("deleted", false)
            ->fetchPairs("id", "id");

        $ownedIds = array_map("intval", array_keys($owned));

        return array_values(array_unique(array_merge($boughtIds, $ownedIds)));
    }

    public function getAllPacks(int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $packs = $this->getPacksTable()
            ->where("deleted", false);

        $count = $packs->count("*");
        $packs = $packs->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function getAllPacksCount(): int
    {
        return $this->getPacksTable()->where("deleted", false)->count("*");
    }

    public function getPackStickers(StickerPack $pack, int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $rels = $this->context->table("stickerpack_relations")
            ->where("stickerpack", $pack->getId());

        $count = $rels->count("*");
        $rels  = $rels->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($rels as $rel) {
            $stickerRec = $this->getStickersTable()->where("id", $rel->sticker)->fetch();
            if ($stickerRec) {
                yield new Sticker($stickerRec);
            }
        }
    }

    public function getPackStickersCount(StickerPack $pack): int
    {
        return $this->context->table("stickerpack_relations")
            ->where("stickerpack", $pack->getId())
            ->count("*");
    }

    public function find(string $query): \Traversable
    {
        $packs = $this->getPacksTable()
            ->where("deleted", false)
            ->where("name LIKE ?", "%$query%");

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function createPack(string $name, string $slug, int $created, User $created_by): StickerPack
    {
        $row = $this->getPacksTable()->insert([
            "name"    => $name,
            "slug"    => $slug,
            "created" => $created,
            "owner_id" => $created_by->getRealId()
        ]);

        return new StickerPack($row);
    }

    public function createSticker(string $emoji = ""): Sticker
    {
        $row = $this->getStickersTable()->insert([
            "emoji" => $emoji,
        ]);

        return new Sticker($row);
    }

    public function getCreatedPacks(User $user, int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $packs = $this->getPacksTable()
            ->where("deleted", false)
            ->where("owner_id", $user->getId());

        $count = $packs->count("*");
        $packs = $packs->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function getCreatedPacksCount(User $user): int
    {
        return $this->getPacksTable()
            ->where("deleted", false)
            ->where("owner_id", $user->getId())
            ->count("*");
    }
}
