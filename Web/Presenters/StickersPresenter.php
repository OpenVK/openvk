<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\Stickers;
use openvk\Web\Models\Repositories\Users;

final class StickersPresenter extends OpenVKPresenter
{
    private $stickers;
    private $users;
    protected $presenterName = "stickers";

    public function __construct(Stickers $stickers, Users $users)
    {
        $this->stickers = $stickers;
        $this->users    = $users;

        parent::__construct();
    }

    public function renderShop(): void
    {
        if ($this->queryParam("act") === "pack") {
            $id = (int) ($this->queryParam("id") ?? 0);
            $pack = $this->stickers->getPack($id);
            if ($pack) {
                $this->redirect("/stickers/" . $pack->getSlug());
                return;
            }
        }

        $act     = (string) ($this->queryParam("act") ?? "shop");
        $section = (string) ($this->queryParam("section") ?? "popular");
        $q       = trim((string) ($this->queryParam("q") ?? ""));

        if ($act === "settings" && !$this->user->identity) {
            $this->redirect("/login");
            return;
        }

        $validActs = ["shop"];
        if ($this->user->identity) {
            $validActs[] = "settings";
            if ($this->user->identity->canCreateStickers()) {
                $validActs[] = "author";
            }
        }

        if (!in_array($act, $validActs, true)) {
            $act = "shop";
        }

        $page       = (int) ($this->queryParam("p") ?? 1);
        $packsCount = 0;
        $packs      = [];

        if ($act === "author") {
            if ($this->user->identity) {
                $packs = iterator_to_array($this->stickers->getCreatedPacks($this->user->identity, $page, 20, $packsCount));
            }
        } elseif ($act === "settings") {
            if ($_SERVER["REQUEST_METHOD"] === "POST" && $this->postParam("action") === "uninstall") {
                $this->assertNoCSRF();
                if (!$this->user->identity) {
                    $this->redirect("/login");
                    return;
                }
                $packId = (int) ($this->postParam("pack_id") ?? 0);
                $packToUninstall = $this->stickers->getPack($packId);
                if ($packToUninstall) {
                    $packToUninstall->uninstall($this->user->identity);
                    $this->flash("succ", tr("stickers"), tr("stickers_pack_uninstalled"));
                }
                $this->redirect("/stickers?act=settings");
                return;
            }

            if ($this->user->identity) {
                $packs = iterator_to_array($this->stickers->getMyPacks($this->user->identity, $page, 20, $packsCount));
            }
        } else {
            if ($q !== "") {
                $packs      = iterator_to_array($this->stickers->find($q));
                if ($section === "free") {
                    $packs = array_values(array_filter($packs, fn($p) => $p->getPrice() === 0));
                }
                $packsCount = count($packs);
            } else {
                $packs = iterator_to_array($this->stickers->getPacks($page, 20, $packsCount, $section));
            }
        }

        $installedPackIds = [];
        $boughtPackIds    = [];
        if ($this->user->identity) {
            $installedPackIds = $this->stickers->getInstalledPackIds($this->user->identity);
            $boughtPackIds    = $this->stickers->getBoughtPackIds($this->user->identity);
        }

        $packSlug = trim((string) ($this->queryParam("pack") ?? ""));
        $autoOpenPack = null;
        if ($packSlug !== "") {
            $autoOpenPack = $this->stickers->getPackBySlug($packSlug);
            if (!$autoOpenPack && is_numeric($packSlug)) {
                $autoOpenPack = $this->stickers->getPack((int) $packSlug);
            }
        }

        $this->template->act              = $act;
        $this->template->section          = $section;
        $this->template->q                = $q;
        $this->template->page             = $page;
        $this->template->packs            = $packs;
        $this->template->packsCount       = $packsCount;
        $this->template->installedPackIds = $installedPackIds;
        $this->template->boughtPackIds    = $boughtPackIds;
        $this->template->purchasedPackIds = $installedPackIds;
        $this->template->canCreate        = $this->user->identity ? $this->user->identity->canCreateStickers() : false;
        $this->template->withdrawTax      = (float) (OPENVK_ROOT_CONF["openvk"]["preferences"]["stickers"]["withdrawTax"] ?? 0);
        $this->template->autoOpenPack     = $autoOpenPack ? $autoOpenPack->getSlug() : null;
    }

    public function renderServeFile($packId, $stickerId, $sizeOrFormat = 512, ?string $format = null): void
    {
        $packId    = (int) $packId;
        $stickerId = (int) $stickerId;

        if (is_numeric($sizeOrFormat)) {
            $size = (int) $sizeOrFormat;
        } else {
            $size = 512;
            if ($format === null && is_string($sizeOrFormat)) {
                $format = $sizeOrFormat;
            }
        }

        if ($size <= 128) {
            $size = 128;
        } elseif ($size <= 256) {
            $size = 256;
        } else {
            $size = 512;
        }

        if ($packId === 0) {
            $stk = $this->stickers->get($stickerId);
            if ($stk && $stk->getPackId()) {
                $packId = $stk->getPackId();
            }
        }

        $dir      = OPENVK_ROOT . "/storage/stickers/$packId/$stickerId/";
        $filePath = null;
        $mime     = "image/webp";

        // 1. Format-specific check
        if ($format === "svg") {
            foreach (["{$size}.svg", "512.svg", "128.svg", "sticker.svg"] as $candidate) {
                if (file_exists($dir . $candidate)) {
                    $filePath = $dir . $candidate;
                    $mime     = "image/svg+xml";
                    break;
                }
            }
        } elseif ($format === "json") {
            foreach (["sticker.json", "lottie.json"] as $candidate) {
                if (file_exists($dir . $candidate)) {
                    $filePath = $dir . $candidate;
                    $mime     = "application/json";
                    break;
                }
            }
        } elseif ($format === "webp") {
            foreach (["{$size}.webp", "512.webp", "256.webp", "128.webp"] as $candidate) {
                if (file_exists($dir . $candidate)) {
                    $filePath = $dir . $candidate;
                    $mime     = "image/webp";
                    break;
                }
            }
        }

        // 2. Fallback if format not found or not specified: check SVG, JSON, WebP
        if (!$filePath) {
            if (file_exists($dir . "sticker.svg") || file_exists($dir . "{$size}.svg")) {
                foreach (["{$size}.svg", "512.svg", "128.svg", "sticker.svg"] as $candidate) {
                    if (file_exists($dir . $candidate)) {
                        $filePath = $dir . $candidate;
                        $mime     = "image/svg+xml";
                        break;
                    }
                }
            } elseif (file_exists($dir . "sticker.json") || file_exists($dir . "lottie.json")) {
                foreach (["sticker.json", "lottie.json"] as $candidate) {
                    if (file_exists($dir . $candidate)) {
                        $filePath = $dir . $candidate;
                        $mime     = "application/json";
                        break;
                    }
                }
            } else {
                foreach (["{$size}.webp", "512.webp", "256.webp", "128.webp"] as $candidate) {
                    if (file_exists($dir . $candidate)) {
                        $filePath = $dir . $candidate;
                        $mime     = "image/webp";
                        break;
                    }
                }
            }
        }

        // 4. Legacy fallback from /public/stickers/{stickerId}/
        if (!$filePath) {
            $legacyDir = OPENVK_ROOT . "/public/stickers/$stickerId/";
            foreach (["{$size}.png", "256.png", "128.png"] as $candidate) {
                if (file_exists($legacyDir . $candidate)) {
                    $filePath = $legacyDir . $candidate;
                    $mime     = "image/png";
                    break;
                }
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            http_response_code(404);
            exit;
        }

        $etag         = '"' . md5_file($filePath) . '"';
        $lastModified = gmdate("D, d M Y H:i:s", filemtime($filePath)) . " GMT";

        header("Content-Type: $mime");
        header("Access-Control-Allow-Origin: *");
        header("Cache-Control: public, max-age=31536000, immutable");
        header("ETag: $etag");
        header("Last-Modified: $lastModified");

        if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && trim($_SERVER["HTTP_IF_NONE_MATCH"]) === $etag) {
            http_response_code(304);
            exit;
        }

        header("Content-Length: " . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function renderCreatePack(): void
    {
        if (!$this->user->identity || !$this->user->identity->canCreateStickers()) {
            $this->flashFail("err", tr("error"), tr("stickers_create_forbidden"));
            $this->redirect("/stickers");
            return;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            $name        = trim((string) ($this->postParam("name") ?? ""));
            $description = trim((string) ($this->postParam("description") ?? ""));
            $slug        = trim((string) ($this->postParam("slug") ?? ""));
            $price       = max(0, (int) ($this->postParam("price") ?? 0));
            $unlisted    = !empty($this->postParam("unlisted"));
            $author      = trim((string) ($this->postParam("author") ?? ""));
            $authorUrl   = trim((string) ($this->postParam("author_url") ?? ""));

            if ($name === "") {
                $this->flashFail("err", tr("error"), tr("stickers_err_no_name"));
                $this->redirect("/stickers/new");
                return;
            }

            if ($slug === "") {
                $slug = "pack-" . time() . "-" . mt_rand(100, 999);
            } else {
                $existing = $this->stickers->getPackBySlug($slug);
                if ($existing) {
                    $slug = $slug . "-" . time() . "-" . mt_rand(10, 99);
                }
            }

            $pack = $this->stickers->createPack($name, $slug, time(), $this->user->identity);
            $pack->setDescription($description);
            $pack->setPrice($price);
            $pack->setUnlisted($unlisted);
            $pack->setAuthor($author !== "" ? $author : $this->user->identity->getCanonicalName());
            if ($authorUrl !== "") {
                $pack->setAuthorUrl($authorUrl);
            }
            $pack->save();

            $mainIndex     = (int) ($this->postParam("main_sticker_index") ?? 0);
            $emojis        = $_POST["emojis"] ?? [];
            $emojis        = is_array($emojis) ? array_values($emojis) : [];
            $savedStickers = [];

            if (isset($_FILES["stickers"]) && is_array($_FILES["stickers"]["tmp_name"])) {
                $count = count($_FILES["stickers"]["tmp_name"]);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES["stickers"]["error"][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName  = $_FILES["stickers"]["tmp_name"][$i];
                    $origName = $_FILES["stickers"]["name"][$i] ?? "";
                    $emoji    = trim((string) ($emojis[$i] ?? ""));

                    $sticker = $this->stickers->createSticker($emoji);
                    if ($sticker->saveFile($tmpName, $pack->getId(), $origName)) {
                        $pack->addSticker($sticker);
                        $savedStickers[] = $sticker;
                    } else {
                        $sticker->delete(true);
                    }
                }
            }

            if (isset($savedStickers[$mainIndex])) {
                $pack->setMainSticker($savedStickers[$mainIndex]);
            } elseif (count($savedStickers) > 0) {
                $pack->setMainSticker($savedStickers[0]);
            } else {
                $pack->setMainSticker(null);
            }
            $pack->save();
            $this->stickers->clearCache($pack->getId());

            $this->flash("succ", tr("admin_stickerpack_saved"), tr("stickers_pack_created"));
            $this->redirect("/stickers?act=author");
            return;
        }

        $this->template->_template        = "Stickers/EditPack.latte";
        $this->template->pack             = null;
        $this->template->isNew            = true;
        $this->template->existingStickers = [];
        $this->template->act              = "author";
        $this->template->withdrawTax      = (float) (OPENVK_ROOT_CONF["openvk"]["preferences"]["stickers"]["withdrawTax"] ?? 0);
    }

    public function renderEditPack($id): void
    {
        $id = (int) $id;
        $pack = $this->stickers->getPack($id);
        if (!$pack) {
            $this->notFound();
            return;
        }

        if (!$pack->canEdit($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("stickers_edit_forbidden"));
            $this->redirect("/stickers");
            return;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            if ($this->postParam("action") === "delete") {
                $pack->delete();
                $this->flash("succ", tr("admin_stickerpack_saved"), tr("stickers_pack_deleted"));
                $this->redirect("/stickers?act=author");
                return;
            }

            if ($this->postParam("action") === "withdraw") {
                $amountParam = $this->postParam("withdraw_amount");
                $amount = ($amountParam !== null && trim((string) $amountParam) !== "") ? (float) $amountParam : null;
                $balance = $pack->getBalance();

                if ($balance <= 0) {
                    $this->flashFail("err", tr("error"), tr("stickers_withdrawal_empty"));
                    $this->redirect("/stickers/edit/" . $pack->getId());
                    return;
                }

                if ($amount !== null && ($amount <= 0 || $amount > $balance)) {
                    $this->flashFail("err", tr("error"), tr("stickers_withdrawal_bad_amount"));
                    $this->redirect("/stickers/edit/" . $pack->getId());
                    return;
                }

                $received = $pack->withdrawCoins($amount);
                $this->flash("succ", tr("stickers"), tr("stickers_withdrawal_success", tr("coins", (int) round($received))));
                $this->redirect("/stickers/edit/" . $pack->getId());
                return;
            }

            $name        = trim((string) ($this->postParam("name") ?? ""));
            $description = trim((string) ($this->postParam("description") ?? ""));
            $slug        = trim((string) ($this->postParam("slug") ?? ""));
            $price       = max(0, (int) ($this->postParam("price") ?? 0));
            $unlisted    = !empty($this->postParam("unlisted"));
            $author      = trim((string) ($this->postParam("author") ?? ""));
            $authorUrl   = trim((string) ($this->postParam("author_url") ?? ""));

            if ($name !== "") {
                $pack->setName($name);
            }
            if ($slug !== "" && $slug !== $pack->getSlug()) {
                $existing = $this->stickers->getPackBySlug($slug);
                if ($existing && $existing->getId() !== $pack->getId()) {
                    $this->flashFail("err", tr("error"), tr("stickers_err_slug_taken"));
                    $this->redirect("/stickers/edit/" . $pack->getId());
                    return;
                }
                $pack->setSlug($slug);
            }
            $pack->setDescription($description);
            $pack->setPrice($price);
            $pack->setUnlisted($unlisted);
            $pack->setAuthor($author !== "" ? $author : null);
            $pack->setAuthorUrl($authorUrl !== "" ? $authorUrl : null);

            // Existing stickers update & delete
            $deleteStickers = $_POST["delete_stickers"] ?? [];
            $deleteStickers = is_array($deleteStickers) ? array_map("intval", $deleteStickers) : [];

            $existingEmojis = $_POST["existing_emojis"] ?? [];
            $existingEmojis = is_array($existingEmojis) ? $existingEmojis : [];

            $existingStickers  = iterator_to_array($pack->getStickers(-1));
            $remainingStickers = [];

            foreach ($existingStickers as $stk) {
                if (in_array($stk->getId(), $deleteStickers, true)) {
                    $pack->removeSticker($stk);
                    $stk->delete();
                } else {
                    if (isset($existingEmojis[$stk->getId()])) {
                        $stk->setEmoji(trim((string) $existingEmojis[$stk->getId()]));
                        $stk->save();
                    }
                    $remainingStickers[] = $stk;
                }
            }

            // Newly uploaded stickers
            $newStickers = [];
            $newEmojis   = $_POST["emojis"] ?? [];
            $newEmojis   = is_array($newEmojis) ? array_values($newEmojis) : [];

            if (isset($_FILES["stickers"]) && is_array($_FILES["stickers"]["tmp_name"])) {
                $count = count($_FILES["stickers"]["tmp_name"]);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES["stickers"]["error"][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName  = $_FILES["stickers"]["tmp_name"][$i];
                    $origName = $_FILES["stickers"]["name"][$i] ?? "";
                    $emoji    = trim((string) ($newEmojis[$i] ?? ""));

                    $sticker = $this->stickers->createSticker($emoji);
                    if ($sticker->saveFile($tmpName, $pack->getId(), $origName)) {
                        $pack->addSticker($sticker);
                        $newStickers[] = $sticker;
                    } else {
                        $sticker->delete(true);
                    }
                }
            }

            // Combine remaining existing stickers and newly added stickers
            $allStickers = array_merge($remainingStickers, $newStickers);

            // Determine cover (main sticker)
            $chosenCover = null;
            $newMainIndex = $this->postParam("new_main_sticker_index");
            if ($newMainIndex !== null && is_numeric($newMainIndex)) {
                $idx = (int) $newMainIndex;
                if (isset($newStickers[$idx])) {
                    $chosenCover = $newStickers[$idx];
                }
            }

            if (!$chosenCover) {
                $mainStickerId = (int) ($this->postParam("main_sticker_id") ?? 0);
                if ($mainStickerId > 0 && !in_array($mainStickerId, $deleteStickers, true)) {
                    foreach ($remainingStickers as $stk) {
                        if ($stk->getId() === $mainStickerId) {
                            $chosenCover = $stk;
                            break;
                        }
                    }
                }
            }

            if ($chosenCover) {
                $pack->setMainSticker($chosenCover);
            } else {
                $currentMain = $pack->getMainSticker();
                if ($currentMain && !in_array($currentMain->getId(), $deleteStickers, true)) {
                    $pack->setMainSticker($currentMain);
                } elseif (count($allStickers) > 0) {
                    // "Если удаляется стикер с обложкой - обложка ставится на первый стикер."
                    $pack->setMainSticker($allStickers[0]);
                } else {
                    // "Если удаляется последний стикер - обложка снимается."
                    $pack->setMainSticker(null);
                }
            }

            $pack->save();
            $this->stickers->clearCache($pack->getId());

            $this->flash("succ", tr("admin_stickerpack_saved"), tr("stickers_pack_saved"));
            $this->redirect("/stickers/edit/" . $pack->getId());
            return;
        }

        $this->template->pack             = $pack;
        $this->template->isNew            = false;
        $this->template->existingStickers = iterator_to_array($pack->getStickers(-1));
        $this->template->act              = "author";
        $this->template->withdrawTax      = (float) (OPENVK_ROOT_CONF["openvk"]["preferences"]["stickers"]["withdrawTax"] ?? 0);
    }

    public function renderViewPack($slug): void
    {
        $slug = (string) $slug;
        $pack = $this->stickers->getPackBySlug($slug);
        if (!$pack && is_numeric($slug)) {
            $pack = $this->stickers->getPack((int) $slug);
            if ($pack) {
                $this->redirect("/stickers/" . $pack->getSlug());
                return;
            }
        }

        if (!$pack || ($pack->isDeleted() && (!$this->user->identity || !$this->user->identity->isAdmin()))) {
            $this->notFound();
            return;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            if (!$this->user->identity) {
                $this->redirect("/login");
                return;
            }

            $action = $this->postParam("action");
            if ($action === "buy" || $action === "install") {
                if ($pack->isPurchasedBy($this->user->identity)) {
                    $this->flash("succ", tr("stickers"), tr("stickers_installed"));
                } elseif ($pack->buy($this->user->identity)) {
                    $this->flash("succ", tr("success"), tr("stickers_pack_purchased"));
                } else {
                    $this->flashFail("err", tr("error"), tr("stickers_not_enough_coins"));
                }
            } elseif ($action === "uninstall") {
                $pack->uninstall($this->user->identity);
                $this->flash("succ", tr("stickers"), tr("stickers_pack_uninstalled"));
            }

            $this->redirect("/stickers?act=shop&section=popular&pack=" . $pack->getSlug());
            return;
        }

        // Direct links redirect to the shop with the pack query param to open modal
        $this->redirect("/stickers?act=shop&section=popular&pack=" . $pack->getSlug());
        return;
    }
}
