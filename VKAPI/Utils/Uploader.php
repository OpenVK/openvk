<?php

declare(strict_types=1);

namespace openvk\VKAPI\Utils;

use openvk\VKAPI\Exceptions\APIErrorException;

class Uploader {
    protected function fail(int $code, string $message): never
    {
        throw new APIErrorException($message, $code);
    }

    function getImagePath(string $photo, string $hash, ?string& $up = null, ?string& $group = null): string
    {
        $secret = CHANDLER_ROOT_CONF["security"]["secret"];
        if (!hash_equals(hash_hmac("sha3-224", $photo, $secret), $hash)) {
            $this->fail(121, "Incorrect hash");
        }

        [$up, $image, $group] = explode("|", $photo);

        $imagePath = __DIR__ . "/../../tmp/api-storage/photos/$up" . "_$image.oct";
        if (!file_exists($imagePath)) {
            $this->fail(10, "Invalid image");
        }

        return $imagePath;
    }
}
