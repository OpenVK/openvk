<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Stickers as StickersRepo;

final class Stickers extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, int $count = 10, int $offset = 0): object
    {
        $this->requireUser();

        if ($user_id < 1) {
            $user_id = $this->getUser()->getId();
        }

        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $repo       = new StickersRepo();
        $packs      = $repo->getMyPacks($this->getUser(), 1, $count);

        $items = [];
        $i = 0;
        foreach ($packs as $pack) {
            if ($i < $offset) {
                $i++;
                continue;
            }

            $data = $pack->toVkApiStruct();
            $stickers = [];
            foreach ($pack->getStickers(1, 100) as $sticker) {
                $stickers[] = [
                    "id"           => $sticker->getId(),
                    "emoji"        => $sticker->getEmoji(),
                    "photo_128"    => $server_url . $sticker->getImageUrl(128),
                    "photo_256"    => $server_url . $sticker->getImageUrl(256),
                    "photo_128_outline" => $server_url . $sticker->getImageUrl(128, true),
                    "photo_256_outline" => $server_url . $sticker->getImageUrl(256, true),
                ];
            }

            $mainSticker = $pack->getMainSticker();

            if ($mainSticker) {
                $data["photo_256"] = $server_url . $mainSticker->getImageUrl(256);
            }

            $data["stickers"] = $stickers;
            $items[] = $data;
            $i++;
        }

        return $this->generateItems($repo->getMyPacksCount($this->getUser()), $items);
    }

    public function getAll(int $count = 10, int $offset = 0): object
    {
        $this->requireUser();

        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $repo       = new StickersRepo();

        $packs = $repo->getPacks(1, $count);

        $items = [];
        $i = 0;
        foreach ($packs as $pack) {
            if ($i < $offset) {
                $i++;
                continue;
            }

            $mainSticker = $pack->getMainSticker();

            $items[] = [
                "id"              => $pack->getId(),
                "name"            => $pack->getName(),
                "description"     => $pack->getDescription() ?? "",
                "slug"            => $pack->getSlug(),
                "price"           => $pack->getPrice(),
                "end_time"        => $pack->getEndTime() ?? 0,
                "purchased"       => $pack->isPurchasedBy($this->getUser()) ? 1 : 0,
                "photo_128"       => $mainSticker ? ($server_url . $mainSticker->getImageUrl(128)) : "",
                "photo_256"       => $mainSticker ? ($server_url . $mainSticker->getImageUrl(256)) : "",
                "stickers_count"  => $pack->getStickersCount(),
            ];
            $i++;
        }

        $count = iterator_to_array($repo->getPacks(1, PHP_INT_MAX));
        return $this->generateItems(sizeof($count), $items);
    }

    public function getFrom(int $stickerpack_id): object
    {
        $this->requireUser();

        $repo = new StickersRepo();
        $pack = $repo->getPack($stickerpack_id);

        if (!$pack) {
            $this->fail(15, "Sticker pack not found");
        }

        if (!$pack->isAvailable()) {
            $this->fail(15, "Sticker pack is not available");
        }

        $canAccess = !$pack->isUnlisted() || $pack->isPurchasedBy($this->getUser());
        if (!$canAccess) {
            $this->fail(15, "Access denied");
        }

        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $stickers = [];

        foreach ($pack->getStickers(1, 100) as $sticker) {
            $stickers[] = [
                "id"           => $sticker->getId(),
                "emoji"        => $sticker->getEmoji(),
                "photo_128"    => $server_url . $sticker->getImageUrl(128),
                "photo_256"    => $server_url . $sticker->getImageUrl(256),
                "photo_128_outline" => $server_url . $sticker->getImageUrl(128, true),
                "photo_256_outline" => $server_url . $sticker->getImageUrl(256, true),
            ];
        }

        return (object) [
            "id"          => $pack->getId(),
            "name"        => $pack->getName(),
            "description" => $pack->getDescription() ?? "",
            "slug"        => $pack->getSlug(),
            "price"       => $pack->getPrice(),
            "purchased"   => $pack->isPurchasedBy($this->getUser()) ? 1 : 0,
            "stickers"    => $stickers,
        ];
    }

    public function buy(int $stickerpack_id): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($stickerpack_id);

        if (!$pack) {
            $this->fail(15, "Sticker pack not found");
        }

        if (!$pack->buy($this->getUser())) {
            $this->fail(15, "Cannot purchase this pack");
        }

        return (object) [
            "success" => 1,
            "pack_id" => $pack->getId(),
        ];
    }
}
