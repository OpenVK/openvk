<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Messages;

use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users;

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
            $first = DB::i()->getContext()->table("stickerpack_relations")
                ->where("stickerpack", $this->getId())
                ->order("id ASC")
                ->fetch();
            if (!$first) {
                return null;
            }
            $mainId = $first->sticker;
        }

        $row = DB::i()->getContext()->table("stickers")->get($mainId);
        if (!$row || $row->deleted) {
            return null;
        }

        $s = new Sticker($row);
        $s->setPackId($this->getId());
        return $s;
    }

    public function canEdit(?\openvk\Web\Models\Entities\User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->getOwnerId() === $user->getId() && $user->canCreateStickers();
    }

    public function getFormat(): string
    {
        $main = $this->getMainSticker();
        return $main ? $main->getFormat($this->getId()) : "webp";
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

    public function getAuthorUrl(): ?string
    {
        return $this->getRecord()->author_url;
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

    public function getOwner(): ?User
    {
        $ownerId = $this->getOwnerId();
        if (!$ownerId) {
            return null;
        }

        return (new Users())->get($ownerId);
    }

    public function getBalance(): float
    {
        return (float) ($this->getRecord()->coins ?? 0.0);
    }

    public function getCoins(): float
    {
        return $this->getBalance();
    }

    public function setCoins(float $coins): void
    {
        $this->stateChanges("coins", $coins);
    }

    public function addCoins(float $coins): float
    {
        $res = $this->getBalance() + $coins;
        $this->setCoins($res);
        $this->save();

        return $res;
    }

    public function withdrawCoins(?float $amount = null): float
    {
        $balance = $this->getBalance();
        if ($balance <= 0) {
            return 0.0;
        }

        if ($amount === null) {
            $amount = $balance;
        } elseif ($amount <= 0 || $amount > $balance) {
            return 0.0;
        }

        $taxPercent = (float) (OPENVK_ROOT_CONF["openvk"]["preferences"]["stickers"]["withdrawTax"] ?? 0);
        $tax        = ($amount / 100) * $taxPercent;
        $received   = $amount - $tax;

        $owner = $this->getOwner();
        if (!$owner) {
            return 0.0;
        }

        $owner->setCoins($owner->getCoins() + $received);
        $this->setCoins($balance - $amount);
        $this->save();
        $owner->save();

        return $received;
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
            if ($stickerRec && !$stickerRec->deleted) {
                $s = new Sticker($stickerRec);
                $s->setPackId($this->getId());
                yield $s;
            }
        }
    }

    public function getStickersCount(): int
    {
        return DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId())
            ->count("*");
    }

    public function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    public function isAvailable(): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        $endTime = $this->getEndTime();
        if (!is_null($endTime) && $endTime < time()) {
            return false;
        }

        return true;
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->where("purchased", 1)
            ->count("*") > 0;
    }

    public function hasBoughtBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->getOwner() && $this->getOwner()->getId() === $user->getId()) {
            return true;
        }

        $row = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if ($row && in_array((int) $row->purchased, [1, 2], true)) {
            return true;
        }

        return false;
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

        $isOwner = $this->getOwner() && $this->getOwner()->getId() === $user->getId();
        $isPreviousPurchase = $existing && (int) $existing->purchased === 2;

        // If previously bought or user is the author, install without deducting coins
        if ($isOwner || $isPreviousPurchase) {
            if ($existing) {
                $existing->update(["purchased" => 1]);
            } else {
                DB::i()->getContext()->table("sticker_purchases")->insert([
                    "user"        => $user->getId(),
                    "stickerpack" => $this->getId(),
                    "purchased"   => 1,
                ]);
            }
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
            $this->addCoins((float) $price);
        }

        if ($existing) {
            $existing->update(["purchased" => 1]);
        } else {
            DB::i()->getContext()->table("sticker_purchases")->insert([
                "user"        => $user->getId(),
                "stickerpack" => $this->getId(),
                "purchased"   => 1,
            ]);
        }

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

    public function uninstall(User $user): bool
    {
        $existing = DB::i()->getContext()->table("sticker_purchases")
            ->where("user", $user->getId())
            ->where("stickerpack", $this->getId())
            ->fetch();

        if (!$existing) {
            return false;
        }

        if ($this->getPrice() > 0 || ($this->getOwner() && $this->getOwner()->getId() === $user->getId())) {
            $existing->update(["purchased" => 2]);
        } else {
            $existing->delete();
        }

        return true;
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

    public function setAuthorUrl(?string $authorUrl): void
    {
        $this->stateChanges("author_url", $authorUrl);
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
        $dir = OPENVK_ROOT . "/storage/stickers/" . $this->getId();
        if (is_dir($dir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
            }
            @rmdir($dir);
        }

        DB::i()->getContext()->table("stickerpack_relations")
            ->where("stickerpack", $this->getId())
            ->delete();

        parent::delete($softly);
    }

    public function toVkApiStruct(?User $user): array
    {
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $mainSticker = $this->getMainSticker();

        return [
            "id"             => $this->getId(),
            "name"           => $this->getName(),
            "description"    => $this->getDescription() ?? "",
            "slug"           => $this->getSlug(),
            "price"          => $this->getPrice(),
            "end_time"       => $this->getEndTime() ?? 0,
            "purchased"      => $this->isPurchasedBy($user) ? 1 : 0,
            "photo_128"      => $mainSticker ? ($server_url . $mainSticker->getImageUrl(128)) : "",
            "photo_256"      => $mainSticker ? ($server_url . $mainSticker->getImageUrl(256)) : "",
            "stickers_count" => $this->getStickersCount(),
            "stickers"       => [],
        ];
    }
}
