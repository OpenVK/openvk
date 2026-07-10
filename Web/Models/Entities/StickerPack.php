<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\User;

class StickerPack extends RowModel
{
    protected $tableName = "stickerpacks";

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getDescription(): ?string
    {
        return $this->getRecord()->description;
    }

    public function getMainSticker(): ?Sticker
    {
        $mainId = $this->getRecord()->main_sticker_id;
        if (!$mainId) {
            $stickersList = iterator_to_array($this->getStickers(1, 2));

            if (sizeof($stickersList) != 0) {
                return $stickersList[0]->getId();
            }
        }

        return new Sticker(DB::i()->getContext()->table("stickers")->get($mainId));
    }

    public function getSlug(): string
    {
        return $this->getRecord()->slug;
    }

    public function getPrice(): int
    {
        return (int) $this->getRecord()->price;
    }

    public function getEndTime(): ?int
    {
        $time = $this->getRecord()->end_time;
        return is_null($time) ? null : (int) $time;
    }

    public function isUnlisted(): bool
    {
        return (bool) $this->getRecord()->unlisted;
    }

    public function getGiftSticker(): ?Sticker
    {
        $giftId = $this->getRecord()->gift_sticker_id;
        if (!$giftId) {
            return null;
        }

        return new Sticker(DB::i()->getContext()->table("stickers")->get($giftId));
    }

    public function getAuthor(): ?string
    {
        return $this->getRecord()->author;
    }

    public function getAuthorId(): ?string
    {
        return $this->getRecord()->author_id;
    }

    public function getAuthorIds(): array
    {
        $csv = $this->getRecord()->author_id;
        if (empty($csv)) {
            return [];
        }

        return array_map("intval", explode(",", $csv));
    }

    public function getOwnerId(): ?int
    {
        $id = $this->getRecord()->owner_id;
        return is_null($id) ? null : (int) $id;
    }

    public function getCreated(): int
    {
        return (int) $this->getRecord()->created;
    }

    public function getStickers(int $page = 1, ?int $perPage = null): \Traversable
    {
        $rels = DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId());

        if ($page !== -1) {
            $rels = $rels->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        }

        foreach ($rels as $rel) {
            $stickerRec = DB::i()->getContext()->table("stickers")->get($rel->sticker);
            if ($stickerRec) {
                yield new Sticker($stickerRec);
            }
        }
    }

    public function getStickersCount(): int
    {
        return DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId())
            ->count("*");
    }

    public function isAvailable(): bool
    {
        if ($this->getRecord()->deleted) {
            return false;
        }

        $endTime = $this->getEndTime();
        if (!is_null($endTime) && $endTime < time()) {
            return false;
        }

        return true;
    }

    public function isPurchasedBy(User $user): bool
    {
        return DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->where("purchased", 1)
            ->count("*") > 0;
    }

    public function getPurchaseStatus(User $user): int
    {
        $row = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if (!$row) {
            return 0;
        }

        return (int) $row->purchased;
    }

    public function buy(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $existing = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if ($existing && (int) $existing->purchased === 1) {
            return false;
        }

        if ($existing && (int) $existing->purchased === 2) {
            $existing->update(["purchased" => 1]);
            return true;
        }

        $price = $this->getPrice();
        $coins = $user->getCoins();

        if ($price > 0 && $coins < $price) {
            return false;
        }

        if ($price > 0) {
            $user->setCoins($coins - $price);
            $user->save();
        }

        DB::i()->getContext()->table("sticker_purchases")->insert([
            "user"       => $user->getId(),
            "stickerpack" => $this->getId(),
            "purchased"  => 1,
        ]);

        return true;
    }

    public function hideFromQuickAccess(User $user): void
    {
        $existing = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if ($existing && (int) $existing->purchased === 1) {
            $existing->update(["purchased" => 2]);
        }
    }

    public function giftTo(User $from, User $to): void
    {
        $price = $this->getPrice();
        $coins = $from->getCoins();

        if ($price > 0 && $coins < $price) {
            return;
        }

        if ($price > 0) {
            $from->setCoins($coins - $price);
            $from->save();
        }

        $existing = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $to->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if ($existing) {
            $existing->update(["purchased" => 1]);
        } else {
            DB::i()->getContext()->table("sticker_purchases")->insert([
                "user"       => $to->getId(),
                "stickerpack" => $this->getId(),
                "purchased"  => 1,
            ]);
        }
    }

    public function setName(string $name): void
    {
        $this->stateChanges("name", $name);
    }

    public function setDescription(?string $description): void
    {
        $this->stateChanges("description", $description);
    }

    public function setMainSticker(?Sticker $sticker): void
    {
        $this->stateChanges("main_sticker_id", $sticker ? $sticker->getId() : null);
    }

    public function setSlug(string $slug): void
    {
        $this->stateChanges("slug", $slug);
    }

    public function setPrice(int $price): void
    {
        $this->stateChanges("price", $price);
    }

    public function setEndTime(?int $endTime): void
    {
        $this->stateChanges("end_time", $endTime);
    }

    public function setUnlisted(bool $unlisted): void
    {
        $this->stateChanges("unlisted", (int) $unlisted);
    }

    public function setAuthor(?string $author): void
    {
        $this->stateChanges("author", $author);
    }

    public function setAuthorId(?string $authorId): void
    {
        $this->stateChanges("author_id", $authorId);
    }

    public function setOwnerId(?int $ownerId): void
    {
        $this->stateChanges("owner_id", $ownerId);
    }

    public function setGiftSticker(?Sticker $sticker): void
    {
        $this->stateChanges("gift_sticker_id", $sticker ? $sticker->getId() : null);
    }

    public function setCreated(int $created): void
    {
        $this->stateChanges("created", $created);
    }

    public function addSticker(Sticker $sticker): void
    {
        $exists = DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId())
            ->where("sticker", $sticker->getId())
            ->count("*");

        if ($exists > 0) {
            return;
        }

        DB::i()->getContext()->table("stickerpack_relations")->insert([
            "stickerpack" => $this->getId(),
            "sticker"     => $sticker->getId(),
        ]);
    }

    public function removeSticker(Sticker $sticker): void
    {
        DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId())
            ->where("sticker", $sticker->getId())
            ->delete();
    }

    public function delete(bool $softly = true): void
    {
        parent::delete($softly);
    }

    public function toVkApiStruct(): array
    {
        return [
            "id"    => $this->getId(),
            "name"  => $this->getName(),
            "slug"  => $this->getSlug(),
            "price" => $this->getPrice(),
        ];
    }
}
