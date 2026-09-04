<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Stickers as StickersRepo;

class Stickers implements Handler
{
    private ?User $user;
    private StickersRepo $stickers;

    public function __construct(?User $user)
    {
        $this->user     = $user;
        $this->stickers = new StickersRepo();
    }

    public function getBalance(int $packId, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(15, "User not authorized");
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack) {
            $reject(15, "No sticker pack with this id found");
            return;
        }

        $owner = $pack->getOwner();
        if (!$owner || $owner->getId() !== $this->user->getId()) {
            $reject(15, "You don't have rights to view this pack's balance");
            return;
        }

        $resolve([
            "balance" => $pack->getBalance(),
        ]);
    }

    public function getWithdrawInfo(int $packId, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(15, "User not authorized");
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack) {
            $reject(15, "No sticker pack with this id found");
            return;
        }

        $owner = $pack->getOwner();
        if (!$owner || $owner->getId() !== $this->user->getId()) {
            $reject(15, "You don't have rights to edit this sticker pack");
            return;
        }

        $tax = (float) (OPENVK_ROOT_CONF["openvk"]["preferences"]["stickers"]["withdrawTax"] ?? 0);

        $resolve([
            "balance" => $pack->getBalance(),
            "tax"     => $tax,
        ]);
    }

    public function withdrawFunds(int $packId, float $amount, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(15, "User not authorized");
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack) {
            $reject(15, "No sticker pack with this id found");
            return;
        }

        $owner = $pack->getOwner();
        if (!$owner || $owner->getId() !== $this->user->getId()) {
            $reject(15, "You don't have rights to edit this sticker pack");
            return;
        }

        $balance = $pack->getBalance();
        if ($balance <= 0) {
            $reject(15, "Balance is empty");
            return;
        }

        $withdrawAmount = $amount > 0 ? $amount : $balance;
        if ($withdrawAmount > $balance) {
            $reject(15, "Withdrawal amount exceeds balance");
            return;
        }

        $received = $pack->withdrawCoins($withdrawAmount);
        $resolve([
            "withdrawn" => $withdrawAmount,
            "received"  => $received,
            "balance"   => $pack->getBalance(),
        ]);
    }

    public function getPackInfo($packIdOrSlug, callable $resolve, callable $reject): void
    {
        $pack = null;
        if (is_numeric($packIdOrSlug)) {
            $pack = $this->stickers->getPack((int) $packIdOrSlug);
        }
        if (!$pack && is_string($packIdOrSlug)) {
            $pack = $this->stickers->getPackBySlug($packIdOrSlug);
        }

        if (!$pack || ($pack->isDeleted() && (!$this->user || !$this->user->isAdmin()))) {
            $reject(15, "Sticker pack not found");
            return;
        }

        $isPurchased = $this->user ? $pack->isPurchasedBy($this->user) : false;
        $isBought    = $this->user ? $pack->hasBoughtBy($this->user) : false;
        $isOwner     = $this->user && $pack->getOwner() && $pack->getOwner()->getId() === $this->user->getId();
        $cover       = $pack->getMainSticker();

        $stickersList = [];
        foreach ($pack->getStickers(-1) as $s) {
            $stickersList[] = [
                "id"     => $s->getId(),
                "emoji"  => $s->getEmoji(),
                "url"    => $s->getImageUrl(128, $pack->getId()),
                "url512" => $s->getImageUrl(512, $pack->getId()),
            ];
        }

        $resolve([
            "id"          => $pack->getId(),
            "name"        => $pack->getName(),
            "slug"        => $pack->getSlug(),
            "description" => $pack->getDescription() ?? "",
            "price"       => $pack->getPrice(),
            "author"      => $pack->getAuthor() ?? "",
            "author_url"  => $pack->getAuthorUrl() ?? "",
            "cover_url"   => $cover ? $cover->getImageUrl(512, $pack->getId()) : null,
            "isPurchased" => $isPurchased,
            "isBought"    => $isBought,
            "isOwner"     => $isOwner,
            "canEdit"     => $pack->canEdit($this->user),
            "isAuthorized"=> (bool) $this->user,
            "stickers"    => $stickersList,
            "count"       => count($stickersList),
        ]);
    }

    public function buyPack(int $packId, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(15, tr("stickers_not_authorized") ?? "Not authorized");
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack || $pack->isDeleted()) {
            $reject(15, "Sticker pack not found");
            return;
        }

        if ($pack->isPurchasedBy($this->user)) {
            $resolve([
                "status"  => "already_installed",
                "message" => tr("stickers_installed"),
            ]);
            return;
        }

        if ($pack->buy($this->user)) {
            $resolve([
                "status"    => "success",
                "message"   => tr("stickers_pack_purchased"),
                "userCoins" => $this->user->getCoins(),
            ]);
        } else {
            $reject(15, tr("stickers_not_enough_coins"));
        }
    }

    public function uninstallPack(int $packId, callable $resolve, callable $reject): void
    {
        if (!$this->user) {
            $reject(15, "Not authorized");
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack) {
            $reject(15, "Sticker pack not found");
            return;
        }

        $pack->uninstall($this->user);
        $resolve([
            "status"  => "uninstalled",
            "message" => tr("stickers_pack_uninstalled"),
        ]);
    }
}
