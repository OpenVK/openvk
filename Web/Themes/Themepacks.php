<?php declare(strict_types=1);
namespace openvk\Web\Themes;
use Nette\InvalidStateException as ISE;
use Chandler\Session\Session;
use Chandler\Patterns\TSimpleSingleton;

class Themepacks implements \ArrayAccess
{
    const THEMPACK_ENGINE_VERSION = 1;
    const DEFAULT_THEME_ID        = "ovk"; # блин было бы смешно если было бы Fore, потому что Лунка, а Luna это название дефолт темы винхп
    
    private $loadedThemepacks = [];
    
    function __construct()
    {
        foreach(glob(OPENVK_ROOT . "/themepacks/*", GLOB_ONLYDIR) as $themeDir) {
            try {
                $theme = Themepack::themepackFromDir($themeDir);
                $tid   = $theme->getId();
                if(isset($this->loadedThemepacks[$tid]))
                    trigger_error("Duplicate theme $tid found at $themeDir, skipping...", E_USER_WARNING);
                else
                    $this->loadedThemepacks[$tid] = $theme;
            } catch(\Exception $e) {
                trigger_error("Could not load theme at $themeDir. Exception: $e", E_USER_WARNING);
            }
        }
    }
    
    private function installUnpacked(string $path): bool
    {
        try {
            $theme = Themepack::themepackFromDir($path);
            $tid   = $theme->getId();
            if(isset($this->loadedThemepacks[$tid]))
                return false;
            
            rename($path, OPENVK_ROOT . "/themepacks/$tid");
            $this->loadedThemepacks[$tid] = $theme;
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
    
    function getThemeList(): \Traversable
    {
        foreach($this->loadedThemepacks as $id => $theme)
            if($theme->isEnabled())
                yield $id => ($theme->getName(Session::i()->get("lang", "ru")));
    }
    
    function getAllThemes(): array
    {
        return $this->loadedThemepacks;
    }
    
    /* ArrayAccess */
    
    function offsetExists($offset): bool
    {
        return $offset === Themepacks::DEFAULT_THEME_ID ? false : isset($this->loadedThemepacks[$offset]);
    }
    
    function offsetGet($offset) : mixed
    {
        return $this->loadedThemepacks[$offset];
    }
    
    function offsetSet($offset, $value): void
    {
        throw new ISE("Theme substitution in runtime is prohbited");
    }
    
    function offsetUnset($offset): void
    {
        $this->uninstall($offset);
    }
    
    /* /ArrayAccess */
    
    function install(string $archivePath): bool
    {
        if(!file_exists($archivePath))
            return false;
        
        $tmpDir = mkdir(tempnam(OPENVK_ROOT . "/tmp/themepack_artifacts/", "themex_"));
        try {
            $archive = new \CabArchive($archivePath);
            $archive->extract($tmpDir);
            
            return $this->installUnpacked($tmpDir);
        } catch (\Exception $e) {
            return false;
        } finally {
            rmdir($tmpDir);
        }
    }
    
    function uninstall(string $id): bool
    {
        if(!isset($loadedThemepacks[$id]))
            return false;
        
        rmdir(OPENVK_ROOT . "/themepacks/$id");
        unset($loadedThemepacks[$id]);
        return true;
    }
    
    use TSimpleSingleton;
}
