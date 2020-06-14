<?php declare(strict_types=1);
namespace openvk\Web\Presenters;

final class BlobPresenter extends OpenVKPresenter
{
    private function getDirName($dir): string
    {
        if(gettype($dir) === "integer") {
            $dir = (string) $dir;
            if(strlen($dir) < 2) #Must have been a number with 1 digit
                $dir = "0$dir";
        }
        
        return $dir;
    }
    
    function renderFile(/*string*/ $dir, string $name, string $format)
    {
        $dir  = $this->getDirName($dir);
        $name = preg_replace("%[^a-zA-Z0-9_\-]++%", "", $name);
        $path = OPENVK_ROOT . "/storage/$dir/$name.$format";
        if(!file_exists($path)) {
            $this->notFound();
        } else {
            if(isset($_SERVER["HTTP_IF_NONE_MATCH"]))
                exit(header("HTTP/1.1 304 Not Modified"));
            
            header("Content-Type: " . mime_content_type($path));
            header("Content-Size: " . filesize($path));
            header("ETag: W/\"" . hash_file("snefru", $path) . "\"");
            
            readfile($path);
            exit;
        }
    }
}
