<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{Sticker, StickerPack, User};
use Nette\Database\Table\ActiveRow;

class Stickers
{
    private $context;
    private $stickers;
    private $packs;

    /* aggressive sql caching */
    private static $cache = [];
    private static $cachePack = [];

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->stickers = $this->context->table("stickers");
        $this->packs    = $this->context->table("stickerpacks");
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
        return self::$cache[$id] ??= $this->toSticker($this->stickers->get($id));
    }

    public function getPack(int $id): ?StickerPack
    {
        return self::$cachePack[$id] ??= $this->toStickerPack($this->packs->get($id));
    }

    public function getPacks(int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $packs = $this->packs
            ->where("deleted", false)
            ->where("unlisted", false);

        $count = $packs->count("*");
        $packs = $packs->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function getMyPacks(User $user, int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $purchases = $this->context->table("sticker_purchases")
            ->where("user", $user->getId());

        $count = $purchases->count("*");
        $purchases = $purchases->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($purchases as $purchase) {
            $packRec = $this->packs->get($purchase->stickerpack);
            if ($packRec && !$packRec->deleted) {
                yield new StickerPack($packRec);
            }
        }
    }

    public function getMyPacksCount(User $user): int
    {
        return $this->context->table("sticker_purchases")
            ->where("user", $user->getId())
            ->count("*");
    }

    public function getAllPacks(int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $packs = $this->packs
            ->where("deleted", false);

        $count = $packs->count("*");
        $packs = $packs->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function getAllPacksCount(): int
    {
        return $this->packs->where("deleted", false)->count("*");
    }

    public function getPackStickers(StickerPack $pack, int $page, ?int $perPage = null, &$count = null): \Traversable
    {
        $rels = $this->context->table("stickerpack_relations")
            ->where("stickerpack", $pack->getId());

        $count = $rels->count("*");
        $rels  = $rels->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);

        foreach ($rels as $rel) {
            $stickerRec = $this->stickers->get($rel->sticker);
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
        $packs = $this->packs
            ->where("deleted", false)
            ->where("name LIKE ?", "%$query%");

        foreach ($packs as $pack) {
            yield new StickerPack($pack);
        }
    }

    public function createPack(string $name, string $slug, int $created, User $created_by): StickerPack
    {
        $row = $this->packs->insert([
            "name"    => $name,
            "slug"    => $slug,
            "created" => $created,
            "owner_id" => $created_by->getRealId()
        ]);

        return new StickerPack($row);
    }

    public function createSticker(string $emoji = ""): Sticker
    {
        $row = $this->stickers->insert([
            "emoji" => $emoji,
        ]);

        return new Sticker($row);
    }
}
