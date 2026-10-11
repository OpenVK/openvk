<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

final class BlobPresenter extends OpenVKPresenter
{
    protected $banTolerant   = true;

    private function getDirName($dir): string
    {
        if (gettype($dir) === "integer") {
            $dir = (string) $dir;
            if (strlen($dir) < 2) { #Must have been a number with 1 digit
                $dir = "0$dir";
            }
        }

        return $dir;
    }

    public function renderFile(/*string*/ $dir, string $name, string $format)
    {
        header("Access-Control-Allow-Origin: *");

        $dir  = $this->getDirName($dir);
        $base = realpath(OPENVK_ROOT . "/storage/$dir");
        $path = realpath(OPENVK_ROOT . "/storage/$dir/$name.$format");
        if (!$path) { # Will also check if file exists since realpath fails on ENOENT
            $this->notFound();
        } elseif (strpos($path, $path) !== 0) { # Prevent directory traversal and storage container escape
            $this->notFound();
        }

        if (isset($_SERVER["HTTP_IF_NONE_MATCH"])) {
            header("HTTP/1.1 304 Not Modified");
            exit();
        }

        $fileSize = filesize($path);
        $mimeType = mime_content_type($path);
        // to fix sometimes misinterpretation and allow official clients to still play audio
        if ($format === "mp3" && ($mimeType === "application/octet-stream" || empty($mimeType))) {
            $mimeType = "audio/mpeg";
        }

        header("Content-Type: " . $mimeType);
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=1210000");
        header("X-Accel-Expires: 1210000");
        header("ETag: W/\"" . hash_file("snefru", $path) . "\"");

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=\s*(\d*)-(\d*)/i', $range, $matches)) {
                $start = !empty($matches[1]) ? intval($matches[1]) : 0;
                $end   = !empty($matches[2]) ? intval($matches[2]) : ($fileSize - 1);

                if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
                    header("HTTP/1.1 416 Range Not Satisfiable");
                    header("Content-Range: bytes */$fileSize");
                    exit;
                }

                $length = $end - $start + 1;

                header("HTTP/1.1 206 Partial Content");
                header("Content-Range: bytes $start-$end/$fileSize");
                header("Content-Length: " . $length);
                header("Content-Size: " . $length);

                $fp = fopen($path, "rb");
                if ($fp) {
                    fseek($fp, $start);
                    $bytesToRead = $length;
                    while (!feof($fp) && $bytesToRead > 0 && !connection_aborted()) {
                        $chunk = min(8192, $bytesToRead);
                        $buffer = fread($fp, $chunk);
                        echo $buffer;
                        flush();
                        $bytesToRead -= strlen($buffer);
                    }
                    fclose($fp);
                }
                exit;
            }
        }

        header("Content-Length: " . $fileSize);
        header("Content-Size: " . $fileSize);
        readfile($path);
        exit;
    }

    public function renderSticker(int $id, $file)
    {
        header("Access-Control-Allow-Origin: *");

        $base = realpath(OPENVK_ROOT . "/public/stickers/" . $id);
        $path = realpath(OPENVK_ROOT . "/public/stickers/" . $id . "/" . $file . ".png");

        if (!$path || strpos($path, $base) !== 0) {
            $this->notFound();
        }

        if (isset($_SERVER["HTTP_IF_NONE_MATCH"])) {
            header("HTTP/1.1 304 Not Modified");
            exit();
        }

        $stickerSize = filesize($path);
        header("Content-Type: image/png");
        header("Content-Length: " . $stickerSize);
        header("Content-Size: " . $stickerSize);
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=1210000");
        header("X-Accel-Expires: 1210000");
        header("ETag: W/\"" . md5_file($path) . "\"");

        readfile($path);
        exit;
    }
}
