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

    public function saveImage(string $file): bool
    {
        $dir = OPENVK_ROOT . "/public/stickers/" . $this->getId() . "/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $image = new \Imagick($file);
            $image->setImageFormat("png");

            $image->resizeImage(256, 256, \Imagick::FILTER_LANCZOS, 1, true);
            $image->writeImage($dir . "256.png");
            
            $image->resizeImage(128, 128, \Imagick::FILTER_LANCZOS, 1, true);
            $image->writeImage($dir . "128.png");

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

            $image->resizeImage(256, 256, \Imagick::FILTER_LANCZOS, 1, true);
            $image->writeImage($dir . "256_outline.png");

            $image->resizeImage(128, 128, \Imagick::FILTER_LANCZOS, 1, true);
            $image->writeImage($dir . "128_outline.png");

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
}
