<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Stickers as StickersRepo;

final class Stickers extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, int $count = 10, int $offset = 0): object
    {
        return (object) [
            'count' => 0,
            'items' => [],
        ];

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

            $data = $pack->toVkApiStruct($this->getUser());
            $stickers = [];
            foreach ($pack->getStickers(1, 100) as $sticker) {
                $stickers[] = $sticker->toVkApiStruct();
            }

            $data["stickers"] = $stickers;
            $items[] = $data;
            $i++;
        }

        return $this->generateItems($repo->getMyPacksCount($this->getUser()), $items);
    }

    public function getAll(int $count = 10, int $offset = 0): object
    {
        return (object) [
            'count' => 0,
            'items' => [],
        ];

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

            $items[] = $pack->toVkApiStruct($this->getUser());
            $i++;
        }

        $count = iterator_to_array($repo->getPacks(1, PHP_INT_MAX));
        return $this->generateItems(sizeof($count), $items);
    }

    public function getFrom(int $stickerpack_id): object
    {
        return (object) [
            'count' => 0,
            'items' => [],
        ];

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

        $pack_item = $pack->toVkApiStruct($this->getUser());

        foreach ($pack->getStickers(1, 100) as $sticker) {
            $pack_item["stickers"][] = $sticker->toVkApiStruct();
        }

        return (object) $pack_item;
    }

    public function buy(int $stickerpack_id): object
    {
        return (object) [
            "success" => 1,
            "pack_id" => 0,
        ];;

        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($stickerpack_id);

        if (!$pack) {
            $this->fail(15, "Sticker pack not found");
        }

        if (!$pack->isAvailable()) {
            $this->fail(15, "Sticker not available");
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
