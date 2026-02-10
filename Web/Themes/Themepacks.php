<?php

declare(strict_types=1);

namespace openvk\Web\Themes;

use Nette\InvalidStateException as ISE;
use Chandler\Session\Session;
use Chandler\Patterns\TSimpleSingleton;

class Themepacks implements \ArrayAccess
{
    use TSimpleSingleton;
    public const THEMPACK_ENGINE_VERSION = 1;
    public const DEFAULT_THEME_ID        = "ovk"; # блин было бы смешно если было бы Fore, потому что Лунка, а Luna это название дефолт темы винхп

    private $loadedThemepacks = [];

    public function __construct()
    {
        foreach (glob(OPENVK_ROOT . "/themepacks/*", GLOB_ONLYDIR) as $themeDir) {
            try {
                $theme = Themepack::themepackFromDir($themeDir);
                $tid   = $theme->getId();
                if (isset($this->loadedThemepacks[$tid])) {
                    trigger_error("Duplicate theme $tid found at $themeDir, skipping...", E_USER_WARNING);
                } else {
                    $this->loadedThemepacks[$tid] = $theme;
                }
            } catch (\Exception $e) {
                trigger_error("Could not load theme at $themeDir. Exception: $e", E_USER_WARNING);
            }
        }
    }

    private function installUnpacked(string $path): bool
    {
        try {
            $theme = Themepack::themepackFromDir($path);
            $tid   = $theme->getId();
            if (isset($this->loadedThemepacks[$tid])) {
                return false;
            }

            rename($path, OPENVK_ROOT . "/themepacks/$tid");
            $this->loadedThemepacks[$tid] = $theme;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getThemeList(): \Traversable
    {
        foreach ($this->loadedThemepacks as $id => $theme) {
            if ($theme->isEnabled()) {
                yield $id => ($theme->getName(Session::i()->get("lang", "ru")));
            }
        }
    }

    public function getAllThemes(): array
    {
        return $this->loadedThemepacks;
    }

    /* ArrayAccess */

    public function offsetExists($offset): bool
    {
        return $offset === Themepacks::DEFAULT_THEME_ID ? false : isset($this->loadedThemepacks[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->loadedThemepacks[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new ISE("Theme substitution in runtime is prohbited");
    }

    public function offsetUnset($offset): void
    {
        $this->uninstall($offset);
    }

    /* /ArrayAccess */

    public function uninstall(string $id): bool
    {
        if (!isset($loadedThemepacks[$id])) {
            return false;
        }

        rmdir(OPENVK_ROOT . "/themepacks/$id");
        unset($loadedThemepacks[$id]);
        return true;
    }
}
