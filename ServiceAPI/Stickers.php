<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Stickers as StickersRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Util\EventRateLimiter;

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
            if (!$pack) {
                $sticker = $this->stickers->getSticker((int) $packIdOrSlug);
                if ($sticker && $sticker->getPackId()) {
                    $pack = $this->stickers->getPack($sticker->getPackId());
                }
            }
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
            $isLottie = ($s->getFormat($pack->getId()) === "lottie");
            $stickersList[] = [
                "id"          => $s->getId(),
                "emoji"       => $s->getEmoji(),
                "url"         => $s->getImageUrl(128, $pack->getId()),
                "url512"      => $s->getImageUrl(512, $pack->getId()),
                "is_animated" => $isLottie,
                "anim_url"    => $isLottie ? $s->getAnimationUrl($pack->getId()) : null,
            ];
        }

        $coverIsLottie = $cover && ($cover->getFormat($pack->getId()) === "lottie");

        $resolve([
            "id"                => $pack->getId(),
            "name"              => $pack->getName(),
            "slug"              => $pack->getSlug(),
            "description"       => $pack->getDescription() ?? "",
            "price"             => $pack->getPrice(),
            "author"            => $pack->getAuthor() ?? "",
            "author_url"        => $pack->getAuthorUrl() ?? "",
            "cover_url"         => $cover ? $cover->getImageUrl(512, $pack->getId()) : null,
            "cover_is_animated" => $coverIsLottie,
            "cover_anim_url"    => $coverIsLottie ? $cover->getAnimationUrl($pack->getId()) : null,
            "is_animated"       => ($pack->getFormat() === "lottie"),
            "isPurchased"       => $isPurchased,
            "isBought"          => $isBought,
            "isOwner"           => $isOwner,
            "canEdit"           => $pack->canEdit($this->user),
            "isAuthorized"      => (bool) $this->user,
            "stickers"          => $stickersList,
            "count"             => count($stickersList),
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

    public function giftPack(int $packId, int $targetUserId, string $message = "", bool $anonymous = false, ?callable $resolve = null, ?callable $reject = null): void
    {
        $resolve ??= fn() => null;
        $reject  ??= fn() => null;

        if (!$this->user) {
            $reject(15, tr("stickers_not_authorized"));
            return;
        }

        if (EventRateLimiter::i()->tryToLimit($this->user, "gifts.send")) {
            $reject(15, tr("limit_exceed_exception"));
            return;
        }

        if ($targetUserId === $this->user->getId()) {
            $reject(15, tr("stickers_gift_self_error"));
            return;
        }

        $targetUser = (new UsersRepo())->get($targetUserId);
        if (!$targetUser || $targetUser->isDeleted()) {
            $reject(15, tr("error_user_not_exists"));
            return;
        }

        if (!$targetUser->canBeViewedBy($this->user)) {
            $reject(15, tr("forbidden"));
            return;
        }

        if (!$targetUser->getPrivacyPermission("gifts.read", $this->user)) {
            $reject(15, tr("forbidden"));
            return;
        }

        $pack = $this->stickers->getPack($packId);
        if (!$pack || $pack->isDeleted() || !$pack->isAvailable()) {
            $reject(15, "Sticker pack not found");
            return;
        }

        if ($pack->hasBoughtBy($targetUser)) {
            $reject(15, tr("stickers_gift_already_owned"));
            return;
        }

        $price = $pack->getPrice();
        if ($price > 0 && $this->user->getCoins() < $price) {
            $reject(15, tr("stickers_not_enough_coins"));
            return;
        }

        $comment = trim($message);
        $res = $pack->giftTo($this->user, $targetUser, $comment !== "" ? $comment : null, $anonymous);
        if ($res) {
            $resolve([
                "status"    => "success",
                "message"   => tr("stickers_gift_success"),
                "userCoins" => $this->user->getCoins(),
            ]);
        } else {
            $reject(15, tr("error_when_gifting"));
        }
    }
}
