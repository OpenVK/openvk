<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Messages;

use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Entities\Attachable;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Stickers;

class Sticker extends Attachable
{
    protected $tableName = "stickers";
    public string $shortName = "sticker";
    private ?int $packId = null;

    public function setPackId(int $packId): void
    {
        $this->packId = $packId;
    }

    public function getPackId(): ?int
    {
        if ($this->packId !== null) {
            return $this->packId;
        }

        $rel = DB::i()->getContext()->table("stickerpack_relations")
            ->where("sticker", $this->getId())
            ->fetch();

        return $this->packId = $rel ? (int) $rel->stickerpack : null;
    }

    public function getPack(): ?StickerPack
    {
        $packId = $this->getPackId();
        if (!$packId) {
            return null;
        }

        return (new Stickers())->getPack($packId);
    }

    public function getOwner(): ?User
    {
        $pack = $this->getPack();
        return $pack ? $pack->getOwner() : null;
    }

    public function isDeleted(): bool
    {
        return (bool) ($this->getRecord()->deleted ?? false);
    }

    public function getAttachmentString(): string
    {
        return $this->shortName . $this->getId();
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        $pack = $this->getPack();
        if ($pack && $pack->isDeleted()) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isDeleted()) {
            return false;
        }

        $pack = $this->getPack();
        if (!$pack || $pack->isDeleted()) {
            return false;
        }

        if ($pack->getOwner() && $pack->getOwner()->getId() === $user->getId()) {
            return true;
        }

        if ($pack->getPrice() <= 0) {
            return true;
        }

        return $pack->hasBoughtBy($user);
    }

    public function getEmoji(): string
    {
        return $this->getRecord()->emoji ?? "";
    }

    public function getFormattedEmoji(): string
    {
        $raw = $this->getEmoji();
        if ($raw === "") {
            return "";
        }

        $emojis = \Emoji\detect_emoji($raw);
        if (!empty($emojis)) {
            $html = "";
            foreach ($emojis as $e) {
                $pt = strtoupper(bin2hex(mb_convert_encoding($e["emoji"], "UTF-16BE", "UTF-8")));
                if ($pt === "2764FE0F") {
                    $pt = "2764";
                }
                $html .= "<span class=\"emoji emoji_$pt\">{$e["emoji"]}</span>";
            }
            return $html;
        }

        $pt = strtoupper(bin2hex(mb_convert_encoding($raw, "UTF-16BE", "UTF-8")));
        if ($pt === "2764FE0F") {
            $pt = "2764";
        }
        return "<span class=\"emoji emoji_$pt\">$raw</span>";
    }

    public function isUnlisted(): bool
    {
        return (bool) $this->getRecord()->unlisted;
    }

    public function setEmoji(string $emoji): void
    {
        $this->stateChanges("emoji", $emoji);
    }

    public function setUnlisted(bool $unlisted): void
    {
        $this->stateChanges("unlisted", (int) $unlisted);
    }

    public function getStorageDir(?int $packId = null): string
    {
        $pid = $packId ?? $this->getPackId() ?? 0;
        return OPENVK_ROOT . "/storage/stickers/" . $pid . "/" . $this->getId() . "/";
    }

    public function getFormat(?int $packId = null): string
    {
        $dir = $this->getStorageDir($packId);
        if (file_exists($dir . "sticker.svg") || file_exists($dir . "512.svg") || file_exists($dir . "128.svg")) {
            return "svg";
        }

        if (file_exists($dir . "sticker.json") || file_exists($dir . "lottie.json")) {
            return "lottie";
        }

        if (file_exists($dir . "512.webp") || file_exists($dir . "128.webp") || file_exists($dir . "256.webp")) {
            return "webp";
        }

        $legacyDir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (file_exists($legacyDir . "128.png")) {
            return "png";
        }

        return "webp";
    }

    public function getImageUrl(int $size = 128, ?int $packId = null): string
    {
        $pid = $packId ?? $this->getPackId() ?? 0;
        $format = $this->getFormat($pid);
        $ext = ($format === "svg") ? "svg" : (($format === "lottie") ? "json" : (($format === "png") ? "png" : "webp"));

        return "/sticker/" . $pid . "/" . $this->getId() . "_" . $size . "." . $ext;
    }

    public function getAnimationUrl(?int $packId = null): ?string
    {
        $pid = $packId ?? $this->getPackId() ?? 0;
        if ($this->getFormat($pid) === "lottie") {
            return "/sticker/" . $pid . "/" . $this->getId() . "_512.json";
        }

        return null;
    }

    public function hasOutline(): bool
    {
        $path = $this->getStorageDir();
        if (file_exists("$path/128_outline.webp") || file_exists("$path/256_outline.webp")) {
            return true;
        }

        $legacyPath = OPENVK_ROOT . "/public/stickers/" . $this->getId();
        return file_exists("$legacyPath/128_outline.png") || file_exists("$legacyPath/256_outline.png");
    }

    public function saveFile(string $file, ?int $packId = null, ?string $originalName = null): bool
    {
        if ($packId !== null) {
            $this->packId = $packId;
        }

        $dir = $this->getStorageDir($packId);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $mime = "";
        if (function_exists("mime_content_type")) {
            $mime = (string) @mime_content_type($file);
        }

        $ext = "";
        if ($originalName) {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        }

        // 1. Vector SVG: keep as is
        if ($mime === "image/svg+xml" || $ext === "svg") {
            copy($file, $dir . "sticker.svg");
            copy($file, $dir . "128.svg");
            copy($file, $dir . "256.svg");
            copy($file, $dir . "512.svg");
            return true;
        }

        // 2. Lottie animation (JSON / TGS)
        if ($ext === "tgs") {
            $content = @file_get_contents($file);
            $decompressed = $content ? @gzdecode($content) : false;
            if ($decompressed !== false) {
                file_put_contents($dir . "sticker.json", $decompressed);
                return true;
            }
        }

        if ($mime === "application/json" || $ext === "json") {
            copy($file, $dir . "sticker.json");
            return true;
        }

        // 3. Raster image: convert to WebP in 3 sizes (128, 256, 512)
        try {
            $image = new \Imagick($file);
            $image->setImageFormat("webp");
            $image->setImageCompressionQuality(90);

            foreach ([128, 256, 512] as $sz) {
                $copy = clone $image;
                $copy->resizeImage($sz, $sz, \Imagick::FILTER_LANCZOS, 1, true);
                $copy->writeImage($dir . $sz . ".webp");
                $copy->clear();
            }

            $image->clear();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    public function saveImage(string $file, ?int $packId = null, ?string $originalName = null): bool
    {
        return $this->saveFile($file, $packId, $originalName);
    }

    public function delete(bool $softly = true): void
    {
        $dir = $this->getStorageDir();
        if (is_dir($dir)) {
            array_map("unlink", glob("$dir*.*") ?: []);
            @rmdir($dir);
        }

        $legacyDir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (is_dir($legacyDir)) {
            array_map("unlink", glob("$legacyDir*.*") ?: []);
            @rmdir($legacyDir);
        }

        parent::delete($softly);
    }

    public function toApiAttachment(?User $user = null): array
    {
        return [
            "type"    => "sticker",
            "sticker" => $this->toVkApiStruct($user),
        ];
    }

    public function toVkApiStruct($userOrPackId = null, ?int $packId = null): array
    {
        $user = null;
        if ($userOrPackId instanceof User) {
            $user = $userOrPackId;
        } elseif (is_int($userOrPackId)) {
            $packId = $userOrPackId;
        }

        $server_url = ovk_scheme(true) . ($_SERVER["HTTP_HOST"] ?? "");
        $pid = $packId ?? $this->getPackId() ?? 0;
        $format = $this->getFormat($pid);

        $images = [];
        $imagesWithBackground = [];
        foreach ([64, 128, 256, 352, 512] as $sz) {
            $imgSz = ($sz <= 128) ? 128 : (($sz <= 256) ? 256 : 512);
            $url = $server_url . $this->getImageUrl($imgSz, $pid);

            $images[] = [
                "url"    => $url,
                "width"  => $sz,
                "height" => $sz,
            ];
            $imagesWithBackground[] = [
                "url"    => $url,
                "width"  => $sz,
                "height" => $sz,
            ];
        }

        $data = [
            "id"                     => $this->getId(),
            "sticker_id"             => $this->getId(),
            "product_id"             => $pid,
            "images"                 => $images,
            "images_with_background" => $imagesWithBackground,
            "is_allowed"             => $user ? $this->canBeUsedBy($user) : true,
            "emoji"                  => $this->getEmoji(),
            "photo_64"               => $server_url . $this->getImageUrl(128, $pid),
            "photo_128"              => $server_url . $this->getImageUrl(128, $pid),
            "photo_256"              => $server_url . $this->getImageUrl(256, $pid),
            "photo_352"              => $server_url . $this->getImageUrl(512, $pid),
            "photo_512"              => $server_url . $this->getImageUrl(512, $pid),
            "width"                  => 512,
            "height"                 => 512,
        ];

        if ($format === "lottie") {
            $animUrl = $server_url . "/sticker/" . $pid . "/" . $this->getId() . "_512.json";
            $data["animation_url"] = $animUrl;
            $data["is_animated"]   = true;
            $data["animations"]    = [
                [
                    "type" => "light",
                    "url"  => $animUrl,
                ],
            ];
        }

        return $data;
    }
}
