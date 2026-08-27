<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;

class Sticker extends RowModel
{
    protected $tableName = "stickers";

    public function getEmoji(): string
    {
        return $this->getRecord()->emoji ?? "";
    }

    public function isUnlisted(): bool
    {
        return (bool) $this->getRecord()->unlisted;
    }

    public function hasOutline(): bool
    {
        $path = OPENVK_ROOT . "/public/stickers/" . $this->getId();
        return file_exists("$path/128_outline.png") || file_exists("$path/256_outline.png");
    }

    public function getImageUrl(int $size = 128, bool $outline = false): string
    {
        $suffix = $outline ? "_outline" : "";
        return "/assets/public/stickers/" . $this->getId() . "/" . $size . $suffix . ".png";
    }

    public function setEmoji(string $emoji): void
    {
        $this->stateChanges("emoji", $emoji);
    }

    public function setUnlisted(bool $unlisted): void
    {
        $this->stateChanges("unlisted", (int) $unlisted);
    }

    private function resizeTo(\Imagick $image, string $dir, int $size, bool $outline = false): void
    {
        $suffix = $outline ? "_outline" : "";
        $copy = clone $image;
        $copy->resizeImage($size, $size, \Imagick::FILTER_LANCZOS, 1, true);
        $copy->writeImage($dir . $size . $suffix . ".png");
        $copy->clear();
    }

    public function saveImage(string $file): bool
    {
        $dir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $image = new \Imagick($file);
            $image->setImageFormat("png");

            $this->resizeTo($image, $dir, 128);
            $this->resizeTo($image, $dir, 256);

            $image->clear();
            return true;
        } catch (\ImagickException $ex) {
            return false;
        }
    }

    public function saveOutline(string $file): bool
    {
        $dir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $image = new \Imagick($file);
            $image->setImageFormat("png");

            $this->resizeTo($image, $dir, 128, true);
            $this->resizeTo($image, $dir, 256, true);

            $image->clear();
            return true;
        } catch (\ImagickException $ex) {
            return false;
        }
    }

    public function delete(bool $softly = true): void
    {
        $dir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (is_dir($dir)) {
            array_map("unlink", glob("$dir*.png"));
            rmdir($dir);
        }

        parent::delete($softly);
    }

    public function toVkApiStruct(): array
    {
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        $res = [];
        $res["id"] = $this->getId();
        $res["emoji"] = $this->getEmoji();
        $res["photo_128"] = $server_url . $this->getImageUrl(128);
        $res["photo_256"] = $server_url . $this->getImageUrl(256);
        $res["photo_128_outline"] = $server_url . $this->getImageUrl(128, true);
        $res["photo_256_outline"] = $server_url . $this->getImageUrl(256, true);

        return $res;
    }
}
