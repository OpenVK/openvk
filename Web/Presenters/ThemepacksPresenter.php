<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Themes\Themepacks;

final class ThemepacksPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    
    function renderResource(string $themepack, string $version, string $resClass, string $resource): void
    {
        if(!isset(Themepacks::i()[$themepack]))
            $this->notFound();
        else
            $theme = Themepacks::i()[$themepack];
        
        if($resClass === "resource") {
            $data = $theme->fetchStaticResource(chandler_escape_url($resource));
        } else if($resClass === "stylesheet") {
            if($resource !== "styles.css")
                $this->notFound();
            else
                $data = $theme->fetchStyleSheet();
        } else {
            $this->notFound();
        }
        
        if(!$data)
            $this->notFound();
        
        header("Content-Type: " . system_extension_mime_type($resource) ?? "text/plain; charset=unknown-8bit");
        header("Content-Size: " . strlen($data));
        header("Cache-Control: public, no-transform, max-age=31536000");
        exit($data);
    }
}
