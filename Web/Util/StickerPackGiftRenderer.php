<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Imagick;
use ImagickPixel;

class StickerPackGiftRenderer
{
    public const SLOTS = [
        ["cx" => 275, "cy" => 120, "size" => 150, "angle" => 0.0],
        ["cx" => 345, "cy" => 165, "size" => 175, "angle" => 16.0],
        ["cx" => 205, "cy" => 165, "size" => 175, "angle" => -16.0],
        ["cx" => 275, "cy" => 235, "size" => 250, "angle" => 0.0],
    ];

    /**
     * Loads a sticker image as an Imagick instance.
     */
    public static function loadStickerImage(int $packId, int $stickerId, ?string $root = null): Imagick
    {
        $root = $root ?? (defined("OPENVK_ROOT") ? OPENVK_ROOT : dirname(__DIR__, 2));
        $dir = $root . "/storage/stickers/{$packId}/{$stickerId}/";
        $candidates = [
            "512.webp",
            "512.png",
            "256.webp",
            "256.png",
            "128.webp",
            "128.png",
        ];

        foreach ($candidates as $cand) {
            $path = $dir . $cand;
            if (file_exists($path) && filesize($path) > 100) {
                try {
                    $im = new Imagick($path);
                    $im->setImageFormat("png");
                    return $im;
                } catch (\Throwable $e) {
                    // Try next candidate
                }
            }
        }

        // Check if lottie / tgs exists and can be rendered
        $lottieCandidates = ["512.json", "512.tgs", "sticker.json", "sticker.tgs"];
        foreach ($lottieCandidates as $cand) {
            $lottiePath = $dir . $cand;
            if (file_exists($lottiePath)) {
                $tmpOut = sys_get_temp_dir() . "/stk_{$packId}_{$stickerId}_" . uniqid() . ".png";
                $nodeScript = $root . "/bin/render_lottie.js";
                if (file_exists($nodeScript)) {
                    $cmd = sprintf(
                        "node %s %s %s 512 0 2>&1",
                        escapeshellarg($nodeScript),
                        escapeshellarg($lottiePath),
                        escapeshellarg($tmpOut)
                    );
                    exec($cmd, $out, $ret);
                    if ($ret === 0 && file_exists($tmpOut) && filesize($tmpOut) > 100) {
                        $im = new Imagick($tmpOut);
                        @unlink($tmpOut);
                        $im->setImageFormat("png");
                        return $im;
                    }
                }
            }
        }

        // Fallback: try to find cover or any available sticker in this pack
        $packDir = $root . "/storage/stickers/{$packId}/";
        if (is_dir($packDir)) {
            $entries = scandir($packDir);
            foreach ($entries as $entry) {
                if ($entry !== "." && $entry !== ".." && is_numeric($entry)) {
                    $altDir = $packDir . $entry . "/";
                    foreach ($candidates as $cand) {
                        $altPath = $altDir . $cand;
                        if (file_exists($altPath) && filesize($altPath) > 100) {
                            try {
                                $im = new Imagick($altPath);
                                $im->setImageFormat("png");
                                return $im;
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                }
            }
        }

        // Final fallback transparent placeholder
        $im = new Imagick();
        $im->newImage(512, 512, new ImagickPixel("transparent"), "png");
        return $im;
    }

    /**
     * Resolves the 4 sticker IDs for the gift composite image:
     * Slots 0, 1, 2: 3 background stickers (top, right, left)
     * Slot 3: front cover sticker
     *
     * @return array<int>
     */
    public static function resolvePackStickerIds(
        int $packId,
        ?int $coverStickerId = null,
        ?array $chosenBackIds = null,
        ?string $root = null
    ): array {
        $root = $root ?? (defined("OPENVK_ROOT") ? OPENVK_ROOT : dirname(__DIR__, 2));
        $packDir = $root . "/storage/stickers/{$packId}";
        $allIds = [];

        if (is_dir($packDir)) {
            $entries = scandir($packDir);
            foreach ($entries as $entry) {
                if ($entry !== "." && $entry !== ".." && is_numeric($entry) && is_dir($packDir . "/" . $entry)) {
                    $allIds[] = (int) $entry;
                }
            }
            sort($allIds, SORT_NUMERIC);
        }

        if (empty($allIds)) {
            return [];
        }

        $cover = $coverStickerId ?? $allIds[0];

        $back = [];
        if (!empty($chosenBackIds)) {
            foreach ($chosenBackIds as $cid) {
                $cid = (int) $cid;
                if (in_array($cid, $allIds, true) && !in_array($cid, $back, true)) {
                    $back[] = $cid;
                }
                if (count($back) >= 3) {
                    break;
                }
            }
        }

        // Fill remaining back slots from available stickers (preferring non-cover ones)
        if (count($back) < 3) {
            foreach ($allIds as $id) {
                if ($id !== $cover && !in_array($id, $back, true)) {
                    $back[] = $id;
                }
                if (count($back) >= 3) {
                    break;
                }
            }
        }

        // If still < 3 (e.g. pack has fewer than 4 stickers), fill with whatever is available
        if (count($back) < 3) {
            foreach ($allIds as $id) {
                if (!in_array($id, $back, true)) {
                    $back[] = $id;
                }
                if (count($back) >= 3) {
                    break;
                }
            }
        }

        // Slot 0 (top-back), Slot 1 (right-back), Slot 2 (left-back), Slot 3 (front cover)
        $slot0 = $back[0] ?? $cover;
        $slot1 = $back[1] ?? $slot0;
        $slot2 = $back[2] ?? $slot1;
        $slot3 = $cover;

        return [$slot0, $slot1, $slot2, $slot3];
    }

    /**
     * Generates a composite giftbox image containing 4 stickers.
     *
     * @param int $packId
     * @param array<int>|null $stickerIds Array of 4 sticker IDs [back0, back1, back2, front3]
     * @param string|null $outputPath Optional file path to save PNG
     * @param string|null $root Root path of OpenVK
     * @param bool $withGlow Whether to render soft glow behind stickers
     * @return Imagick
     */
    public static function render(
        int $packId,
        ?array $stickerIds = null,
        ?string $outputPath = null,
        ?string $root = null,
        bool $withGlow = true
    ): Imagick {
        $root = $root ?? (defined("OPENVK_ROOT") ? OPENVK_ROOT : dirname(__DIR__, 2));
        $backPath = $root . "/Web/static/img/giftbox_back.png";
        $frontPath = $root . "/Web/static/img/giftbox_front.png";

        if (!file_exists($backPath)) {
            throw new \RuntimeException("Giftbox back image not found: {$backPath}");
        }
        if (!file_exists($frontPath)) {
            throw new \RuntimeException("Giftbox front image not found: {$frontPath}");
        }

        if ($stickerIds === null || empty($stickerIds)) {
            $stickerIds = self::resolvePackStickerIds($packId, null, null, $root);
        }

        $back = new Imagick($backPath);
        $front = new Imagick($frontPath);

        $canvasWidth = $back->getImageWidth();
        $canvasHeight = $back->getImageHeight();

        $canvas = new Imagick();
        $canvas->newImage($canvasWidth, $canvasHeight, new ImagickPixel("transparent"), "png");
        $canvas->compositeImage($back, Imagick::COMPOSITE_OVER, 0, 0);

        foreach (self::SLOTS as $idx => $slot) {
            $stIdx = $slot["index"] ?? $idx;
            $stickerId = $stickerIds[$stIdx] ?? null;
            if ($stickerId === null) {
                continue;
            }

            $st = self::loadStickerImage($packId, (int) $stickerId, $root);
            $st->resizeImage($slot["size"], $slot["size"], Imagick::FILTER_LANCZOS, 1);

            if (abs($slot["angle"]) > 0.01) {
                $st->rotateImage(new ImagickPixel("transparent"), $slot["angle"]);
            }

            $sw = $st->getImageWidth();
            $sh = $st->getImageHeight();
            $x = (int) round($slot["cx"] - $sw / 2);
            $y = (int) round($slot["cy"] - $sh / 2);

            // Render soft white glow behind sticker
            if ($withGlow) {
                $glow = clone $st;
                $glow->setImageBackgroundColor(new ImagickPixel("white"));
                $glow->shadowImage(80, 5, 0, 0);

                $gw = $glow->getImageWidth();
                $gh = $glow->getImageHeight();
                $gx = (int) round($slot["cx"] - $gw / 2);
                $gy = (int) round($slot["cy"] - $gh / 2);

                $canvas->compositeImage($glow, Imagick::COMPOSITE_OVER, $gx, $gy);
                $glow->clear();
            }

            $canvas->compositeImage($st, Imagick::COMPOSITE_OVER, $x, $y);
            $st->clear();
        }

        $canvas->compositeImage($front, Imagick::COMPOSITE_OVER, 0, 0);

        if ($outputPath !== null) {
            $canvas->writeImage($outputPath);
        }

        $back->clear();
        $front->clear();

        return $canvas;
    }

    /**
     * Generates gift composite image and returns raw PNG blob.
     */
    public static function renderBlob(
        int $packId,
        ?array $stickerIds = null,
        ?string $root = null,
        bool $withGlow = true
    ): string {
        $img = self::render($packId, $stickerIds, null, $root, $withGlow);
        $img->setImageFormat("png");
        $blob = $img->getImageBlob();
        $img->clear();
        return $blob;
    }
}
