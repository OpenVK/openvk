<?php

declare(strict_types=1);

namespace openvk\Web\Themes;

use ScssPhp\ScssPhp\Compiler as CSSCompiler;
use ScssPhp\ScssPhp\OutputStyle;

class Themepack
{
    private $id;
    private $ver;
    private $inh;
    private $tpl;
    private $meta;
    private $home;
    private $enabled;
    private $commonFaviconURL;
    private $favicons;
    private $has_styles;
    private $styles;

    private $cssExtensions = [
        "css",
        "scss",
    ];

    public function __construct(string $id, string $ver, bool $inh, bool $tpl, bool $enabled, object $meta, object $manifest)
    {
        $this->id      = $id;
        $this->ver     = $ver;
        $this->inh     = $inh;
        $this->tpl     = $tpl;
        $this->meta    = $meta;
        $this->home    = OPENVK_ROOT . "/themepacks/$id";
        $this->enabled = $enabled;
        if ($manifest->commonFaviconURL) {
            $this->commonFaviconURL = $manifset->commonFaviconURL;
        }
        if ($manifest->favicons) {
            $this->favicons = $manifset->favicons;
        } else {
            $this->favicons = [
                "im" => "",
                "audio_playing" => "",
                "audio_stopped" => "",
            ];
        }
        $this->has_styles = $manifest->has_styles ?? false;
        $this->styles = $manifest->styles ?? [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        $disabled = OPENVK_ROOT_CONF["openvk"]["preferences"]["themepacks"]["disabled"];

        if ($disabled && in_array($this->getId(), $disabled)) {
            return false;
        }

        return $this->enabled;
    }

    public function hasStylesheet(): bool
    {
        return true;
    }

    public function getFaviconURL(): string
    {
        if ($this->commonFaviconURL != null) {
            return $this->commonFaviconURL;
        }

        return "/assets/packages/static/openvk/img/favicon/main.ico";
    }

    public function getAccentColor(): string
    {
        return $this->enabled;
    }

    public function getName(?string $lang = null): string
    {
        if (!$this->meta->name) {
            return $this->getId() . " theme";
        } elseif (is_array($this->meta->name)) {
            return $this->meta->name[$lang ?? "_"] ?? $this->getId() . " theme";
        } else {
            return $this->meta->name;
        }
    }

    public function getBaseDir(): string
    {
        return $this->home;
    }

    public function getVersion(): string
    {
        return $this->ver;
    }

    public function getDescription(): string
    {
        return $this->meta->description ?? "A theme with name \"" . $this->getName() . "\"";
    }

    public function getAuthor(): string
    {
        return $this->meta->author ?? $this->getName() . " authors";
    }

    public function inheritDefault(): bool
    {
        return $this->inh;
    }

    public function overridesTemplates(): bool
    {
        return $this->tpl;
    }

    public function hasStyles(): bool
    {
        return $this->has_styles;
    }

    public function getStyles(): array
    {
        return [];
    }

    public function fetchResource(string $resource, bool $processCSS = false): ?string
    {
        $file = "$this->home/$resource";
        if (!file_exists($file)) {
            return null;
        }

        $result = file_get_contents($file);
        if (in_array(@end(explode(".", $resource)), $this->cssExtensions) && $processCSS) {
            $compiler = new CSSCompiler([ "cacheDir" => OPENVK_ROOT . "/tmp" ]);
            $compiler->setOutputStyle(OutputStyle::COMPRESSED);

            $result = $compiler->compileString($result, $file)->getCSS();
        }

        return $result;
    }

    public function fetchStyleSheet(): ?string
    {
        return $this->fetchResource("stylesheet.scss", true) ?? $this->fetchResource("stylesheet.css", true);
    }

    public function fetchStaticResource(string $name): ?string
    {
        return $this->fetchResource("res/$name");
    }

    public static function themepackFromDir(string $dirname): Themepack
    {
        $manifestFile = "$dirname/theme.yml";
        if (!file_exists($manifestFile)) {
            throw new Exceptions\NotThemeDirectoryException("Could not locate manifest at $dirname");
        }

        $manifest = (object) chandler_parse_yaml($manifestFile);
        if (!isset($manifest->id) || !isset($manifest->version) || !isset($manifest->openvk_version) || !isset($manifest->metadata)) {
            throw new Exceptions\MalformedManifestException("Manifest is missing required information");
        }

        if ($manifest->openvk_version > Themepacks::THEMPACK_ENGINE_VERSION) {
            throw new Exceptions\IncompatibleThemeException("Theme is built for newer OVK (themeEngine" . $manifest->openvk_version . ")");
        }

        return new Themepack($manifest->id, $manifest->version, (bool) ($manifest->inherit_master ?? true), (bool) ($manifest->override_templates ?? false), (bool) ($manifest->enabled ?? true), (object) $manifest->metadata, $manifest);
    }
}

/* Special themepacks */

class DefaultThemepack
{
    public function hasStyles(): bool { return true; }
    public function isEnabled(): bool { return true; }
    public function overridesTemplates(): bool { return false; }
    public function inheritDefault(): bool { return true; }
    public function getId(): string { return "ovk"; }
    public function getVersion(): string { return "actual"; }
    public function getFaviconURL(): string { return "/assets/packages/static/openvk/img/favicon/main.ico"; }
    public function hasStylesheet(): bool { return false; }

    public function getStyles(): array
    {
        return [
            "css/revisions/modern_controls.css"
        ];
    }

    public function getName(): string
    {
        return "OpenVK (" . tr("default") . ")";
    }
}

class OpenVKIn2019_2026Themepack
{
    public function hasStyles(): bool { return true; }
    public function isEnabled(): bool { return true; }
    public function overridesTemplates(): bool { return false; }
    public function inheritDefault(): bool { return true; }
    public function getId(): string { return "ovk1"; }
    public function getVersion(): string { return "actual"; }
    public function getFaviconURL(): string { return "/assets/packages/static/openvk/img/favicon/main.ico"; }
    public function hasStylesheet(): bool { return false; }
    public function getName(): string
    {
        return "OpenVK " . mb_strtolower(tr("openvk_themepack1"));
    }
    public function getStyles(): array
    {
        return [];
    }
}

class MobileThemepack
{
    public function hasStyles(): bool { return false; }
    public function isEnabled(): bool { return false; }
    public function overridesTemplates(): bool { return false; }
    public function inheritDefault(): bool { return true; }
    public function getId(): string { return "mobile_ovk"; }
    public function getVersion(): string { return "actual"; }
    public function getFaviconURL(): string { return "/assets/packages/static/openvk/img/favicon/main.ico"; }
    public function hasStylesheet(): bool { return false; }
    public function getName(): string
    {
        return "OpenVK Mobile";
    }
}
