<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\Messages\StickerPack;
use openvk\Web\Models\Entities\Messages\Sticker;
use openvk\Web\Models\Repositories\Stickers as StickersRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Store extends VKAPIRequestHandler
{
    private static ?array $emojiKeywords = null;

    public static function getEmojiKeywords(): array
    {
        if (self::$emojiKeywords !== null) {
            return self::$emojiKeywords;
        }

        $root = defined("OPENVK_ROOT") ? OPENVK_ROOT : dirname(__DIR__, 2);
        $tsvPath = $root . "/data/emoji-keywords.tsv";
        $cacheDir = $root . "/tmp/cache";
        $cachePath = $cacheDir . "/emoji-keywords.php";

        if (file_exists($cachePath) && file_exists($tsvPath) && filemtime($cachePath) >= filemtime($tsvPath)) {
            $cached = @include $cachePath;
            if (is_array($cached)) {
                self::$emojiKeywords = $cached;
                return self::$emojiKeywords;
            }
        }

        $map = [];
        if (file_exists($tsvPath)) {
            $lines = file($tsvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === "" || str_starts_with($line, "#")) {
                    continue;
                }

                $colonPos = mb_strpos($line, ":");
                if ($colonPos === false) {
                    continue;
                }

                $emoji = trim(mb_substr($line, 0, $colonPos));
                $rawKw = trim(mb_substr($line, $colonPos + 1));
                if ($emoji === "" || $rawKw === "") {
                    continue;
                }

                $kws = array_values(array_filter(array_map("trim", explode(",", $rawKw)), fn($k) => $k !== ""));
                if (!empty($kws)) {
                    $map[$emoji] = $kws;
                }
            }
        }

        $lines = [];
        foreach ($map as $emoji => $kws) {
            $encodedEmoji = json_encode($emoji, JSON_UNESCAPED_UNICODE);
            $encodedKws = json_encode(array_values($kws), JSON_UNESCAPED_UNICODE);
            $lines[] = "    {$encodedEmoji} => {$encodedKws}";
        }

        $code = "<?php\n\ndeclare(strict_types=1);\n\n// Auto-generated from emoji-keywords.tsv - do not edit directly\nreturn [\n" . implode(",\n", $lines) . ",\n];\n";
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        @file_put_contents($cachePath, $code, LOCK_EX);

        self::$emojiKeywords = $map;
        return self::$emojiKeywords;
    }

    private function formatProduct(StickerPack $pack, bool $extended = true): array
    {
        $server_url = ovk_scheme(true) . ($_SERVER["HTTP_HOST"] ?? "");
        $mainSticker = $pack->getMainSticker();

        $isAnimated = ($mainSticker && $mainSticker->getFormat($pack->getId()) === "lottie");
        $animUrl = $isAnimated ? $server_url . $mainSticker->getAnimationUrl($pack->getId()) : null;

        $user = $this->getUser();
        $isPurchased = $user ? $pack->isPurchasedBy($user) : false;
        $hasBought = $user ? $pack->hasBoughtBy($user) : false;
        $isActive = $isPurchased ? 1 : 0;
        $purchased = ($isPurchased || $hasBought) ? 1 : 0;

        $stickerIds = $pack->getStickerIds();
        $stickersList = [];
        foreach ($stickerIds as $sid) {
            $stickersList[] = [
                "sticker_id" => (int) $sid,
                "is_allowed" => true,
            ];
        }

        $thumb128 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(128, (int) $pack->getId())) : "";
        $thumb256 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(256, (int) $pack->getId())) : "";
        $thumb512 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(512, (int) $pack->getId())) : "";

        $previews = [];
        foreach (array_slice($stickerIds, 0, 4) as $sid) {
            $previews[] = [
                "photo_128" => $server_url . "/images/stickers/{$sid}/128b.png",
                "photo_256" => $server_url . "/images/stickers/{$sid}/256b.png",
                "photo_512" => $server_url . "/images/stickers/{$sid}/512.png",
            ];
        }

        $price = (int) $pack->getPrice();
        $priceStr = $price > 0 ? "{$price} " . tr("coins") : "Бесплатно";

        $gift = $pack->getGift();
        $giftData = null;
        if ($gift) {
            $giftData = [
                "id"                  => (int) $gift->getId(),
                "stickers_product_id" => (int) $pack->getId(),
                "thumb_256"           => $server_url . "/images/gift/" . $gift->getId() . "/256.png",
                "thumb_96"            => $server_url . "/images/gift/" . $gift->getId() . "/96.png",
                "thumb_48"            => $server_url . "/images/gift/" . $gift->getId() . "/48.png",
            ];
        }

        return [
            "id"            => (int) $pack->getId(),
            "type"          => "stickers",
            "title"         => $pack->getName(),
            "name"          => $pack->getName(),
            "description"   => $pack->getDescription() ?? "",
            "author"        => $pack->getAuthor() ?? ($pack->getOwner() ? $pack->getOwner()->getCanonicalName() : ""),
            "purchased"     => $purchased,
            "active"        => $isActive,
            "promoted"      => 0,
            "purchase_date" => (int) $pack->getCreated(),
            "price"         => $price,
            "price_str"     => $priceStr,
            "photo_35"      => $thumb128,
            "photo_70"      => $thumb128,
            "photo_140"     => $thumb128,
            "photo_296"     => $thumb256,
            "photo_592"     => $thumb512,
            "photo_128"     => $thumb128,
            "photo_256"     => $thumb256,
            "photo_512"     => $thumb512,
            "base_url"      => $server_url . "/images/stickers/",
            "has_animation" => $isAnimated,
            "is_animated"   => $isAnimated,
            "animation_url" => $animUrl,
            "stickers_count" => count($stickerIds),
            "sticker_ids"   => $stickerIds,
            "stickers"      => $stickersList,
            "previews"      => $previews,
            "gift"          => $giftData,
        ];
    }

    public function getProducts(
        string $type = "stickers",
        string $filters = "",
        int $extended = 1,
        int $count = 50,
        int $offset = 0,
        $product_ids = "",
        int $user_id = 0
    ): object|array {
        $repo = new StickersRepo();
        $targetUser = $this->getUser();
        if ($user_id > 0) {
            $targetUser = (new UsersRepo())->get($user_id) ?? $targetUser;
        }

        $items = [];
        $totalCount = 0;

        $filterList = array_filter(array_map('trim', explode(',', strtolower($filters))));
        $hasProductIds = !empty($product_ids);

        if ($hasProductIds) {
            $ids = is_array($product_ids) ? $product_ids : array_map('intval', explode(',', (string) $product_ids));
            foreach ($ids as $id) {
                $pack = $repo->getPack($id);
                if ($pack && !$pack->isDeleted() && $pack->isAvailable()) {
                    $items[] = $this->formatProduct($pack, (bool) $extended);
                }
            }
            $totalCount = count($items);
        } elseif (in_array("purchased", $filterList, true)) {
            if (!$targetUser) {
                $this->requireUser();
            }
            $boughtIds = $repo->getBoughtPackIds($targetUser);
            $totalCount = count($boughtIds);
            $slice = array_slice($boughtIds, $offset, $count);
            foreach ($slice as $id) {
                $pack = $repo->getPack($id);
                if ($pack && !$pack->isDeleted()) {
                    $items[] = $this->formatProduct($pack, (bool) $extended);
                }
            }
        } elseif (in_array("active", $filterList, true) || empty($filters)) {
            if (!$targetUser) {
                $this->requireUser();
            }
            $activePacks = iterator_to_array($repo->getMyPacks($targetUser, 1, PHP_INT_MAX));
            $totalCount = count($activePacks);
            $slice = array_slice($activePacks, $offset, $count);
            foreach ($slice as $pack) {
                $items[] = $this->formatProduct($pack, (bool) $extended);
            }
        } else {
            $allPacks = iterator_to_array($repo->getPacks(1, PHP_INT_MAX, $totalCount, "all"));
            $slice = array_slice($allPacks, $offset, $count);
            foreach ($slice as $pack) {
                $items[] = $this->formatProduct($pack, (bool) $extended);
            }
        }

        return (object) [
            "count" => $totalCount,
            "items" => $items,
        ];
    }

    public function getStockItems(
        string $type = "stickers",
        string $section = "",
        int $extended = 1,
        int $count = 50,
        int $offset = 0,
        string $merchant = ""
    ): object {
        $repo = new StickersRepo();
        $totalCount = 0;
        $page = (int) floor($offset / max($count, 1)) + 1;

        $secParam = match ($section) {
            "free" => "free",
            "all", "catalog" => "all",
            default => "popular",
        };
        $packs = iterator_to_array($repo->getPacks(1, PHP_INT_MAX, $totalCount, $secParam));

        $slice = array_slice($packs, $offset, $count);
        $items = [];

        foreach ($slice as $pack) {
            $product = $this->formatProduct($pack, (bool) $extended);
            $price = $pack->getPrice();
            $priceStr = $price > 0 ? "{$price} " . tr("coins") : "Бесплатно";

            $items[] = [
                "product"       => $product,
                "description"   => $pack->getDescription() ?? "",
                "author"        => $pack->getAuthor() ?? ($pack->getOwner() ? $pack->getOwner()->getCanonicalName() : ""),
                "price"         => $price,
                "price_str"     => $priceStr,
                "can_purchase"  => 1,
                "free"          => $price === 0 ? 1 : 0,
                "is_new"        => (time() - $pack->getCreated() < 30 * 86400) ? 1 : 0,
            ];
        }

        return (object) [
            "count" => count($packs),
            "items" => $items,
        ];
    }

    public function getStickersKeywords(
        int $aliases = 1,
        int $all_products = 1,
        int $need_stickers = 1,
        string $stickers_hash = "",
        string $products_hash = "",
        int $count = 0,
        int $user_id = 0
    ): object {
        $repo = new StickersRepo();
        $user = $this->getUser();
        if ($user_id > 0) {
            $user = (new UsersRepo())->get($user_id) ?? $user;
        }

        $packs = [];
        if ($user) {
            $packs = iterator_to_array($repo->getMyPacks($user, 1, PHP_INT_MAX));
        }

        if (empty($packs) || $all_products === 1) {
            $allAvailable = iterator_to_array($repo->getPacks(1, 100));
            $existingIds = array_map(fn($p) => $p->getId(), $packs);
            foreach ($allAvailable as $p) {
                if (!in_array($p->getId(), $existingIds, true)) {
                    $packs[] = $p;
                }
            }
        }

        $stickerMap = [];
        $wordMap = [];
        foreach ($packs as $pack) {
            foreach ($pack->getStickers(-1) as $sticker) {
                $sid = $sticker->getId();
                if ($need_stickers === 1 && !isset($stickerMap[$sid])) {
                    $stickerMap[$sid] = $sticker->toVkApiStruct($user, $pack->getId());
                }

                $rawEmoji = $sticker->getEmoji();
                if ($rawEmoji === "") {
                    continue;
                }

                $emojis = \Emoji\detect_emoji($rawEmoji);
                $emojiChars = !empty($emojis) ? array_column($emojis, "emoji") : [$rawEmoji];

                foreach ($emojiChars as $eChar) {
                    $eChar = trim($eChar);
                    if ($eChar === "") {
                        continue;
                    }

                    if (!isset($wordMap[$eChar])) {
                        $wordMap[$eChar] = [];
                    }
                    if (!in_array($sid, $wordMap[$eChar], true)) {
                        $wordMap[$eChar][] = $sid;
                    }

                    $emojiKwMap = self::getEmojiKeywords();
                    if ($aliases === 1 && isset($emojiKwMap[$eChar])) {
                        foreach ($emojiKwMap[$eChar] as $kw) {
                            $kw = mb_strtolower(trim($kw));
                            if (!isset($wordMap[$kw])) {
                                $wordMap[$kw] = [];
                            }
                            if (!in_array($sid, $wordMap[$kw], true)) {
                                $wordMap[$kw][] = $sid;
                            }
                        }
                    }
                }
            }
        }

        $stickerGroups = [];
        foreach ($wordMap as $word => $sids) {
            sort($sids);
            $key = implode(",", $sids);
            if (!isset($stickerGroups[$key])) {
                $stickerGroups[$key] = [
                    "words"         => [],
                    "user_stickers" => $sids,
                ];
            }
            $stickerGroups[$key]["words"][] = $word;
        }

        $dictionary = [];
        foreach ($stickerGroups as $group) {
            $userStickers = [];
            foreach ($group["user_stickers"] as $sid) {
                $sidInt = (int) $sid;
                if ($need_stickers === 1 && isset($stickerMap[$sidInt])) {
                    $item = $stickerMap[$sidInt];
                    $item["sticker_id"] = $sidInt;
                    $item["id"] = $sidInt;
                    $userStickers[] = $item;
                } else {
                    $userStickers[] = [
                        "id"         => $sidInt,
                        "sticker_id" => $sidInt,
                    ];
                }
            }

            $dictionary[] = [
                "words"             => array_values(array_map('strval', $group["words"])),
                "user_stickers"     => $userStickers,
                "promoted_stickers" => [],
            ];
        }

        $server_url = ovk_scheme(true) . ($_SERVER["HTTP_HOST"] ?? "");

        return (object) [
            "count"         => count($dictionary),
            "dictionary"    => $dictionary,
            "base_url"      => $server_url . "/images/stickers/",
            "stickers_hash" => md5(json_encode($dictionary)),
            "products_hash" => md5(json_encode(array_keys($dictionary))),
        ];
    }

    public function activateProduct(int $product_id = 0, string $type = "stickers"): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($product_id);
        if (!$pack) {
            $this->fail(15, "Product not found");
        }

        if ($pack->getPrice() === 0 || $pack->hasBoughtBy($this->getUser())) {
            $pack->buy($this->getUser());
        }

        return (object) ["success" => 1];
    }

    public function deactivateProduct(int $product_id = 0, string $type = "stickers"): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($product_id);
        if (!$pack) {
            $this->fail(15, "Product not found");
        }

        $pack->hideFromQuickAccess($this->getUser());

        return (object) ["success" => 1];
    }

    public function buy(int $product_id = 0, int $stickerpack_id = 0): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $id = $product_id > 0 ? $product_id : $stickerpack_id;
        $repo = new StickersRepo();
        $pack = $repo->getPack($id);

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
            "success"    => 1,
            "product_id" => $pack->getId(),
            "pack_id"    => $pack->getId(),
        ];
    }

    public function getFavoriteStickers(): object
    {
        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    public function addFavoriteSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function removeFavoriteSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function getRecentStickers(): object
    {
        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    public function addRecentSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function clearRecentStickers(): object
    {
        return (object) ["success" => 1];
    }
}
