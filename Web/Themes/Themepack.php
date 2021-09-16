<?php declare(strict_types=1);
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
    
    private $cssExtensions = [
        "css",
        "scss",
    ];
    
    function __construct(string $id, string $ver, bool $inh, bool $tpl, bool $enabled, object $meta)
    {
        $this->id      = $id;
        $this->ver     = $ver;
        $this->inh     = $inh;
        $this->tpl     = $tpl;
        $this->meta    = $meta;
        $this->home    = OPENVK_ROOT . "/themepacks/$id";
        $this->enabled = $enabled;
    }
    
    function getId(): string
    {
        return $this->id;
    }
    
    function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    function getName(?string $lang = NULL): string
    {
        if(!$this->meta->name)
            return $this->getId() . " theme";
        else if(is_array($this->meta->name))
            return $this->meta->name[$lang ?? "_"] ?? $this->getId() . " theme";
        else
            return $this->meta->name;
    }
    
    function getBaseDir(): string
    {
        return $this->home;
    }
    
    function getVersion(): string
    {
        return $this->ver;
    }
    
    function getDescription(): string
    {
        return $this->meta->description ?? "A theme with name \"" . $this->getName() . "\"";
    }
    
    function getAuthor(): string
    {
        return $this->meta->author ?? $this->getName() . " authors";
    }
    
    function inheritDefault(): bool
    {
        return $this->inh;
    }
    
    function overridesTemplates(): bool                                                                                                                                        
    {                                                                                                                                                                          
        return $this->tpl;                                                                                                                                                     
    }
    
    function fetchResource(string $resource, bool $processCSS = false): ?string
    {
        $file = "$this->home/$resource";
        if(!file_exists($file))
            return NULL;
        
        $result = file_get_contents($file);
        if(in_array(@end(explode(".", $resource)), $this->cssExtensions) && $processCSS) {
            $compiler = new CSSCompiler([ "cacheDir" => OPENVK_ROOT . "/tmp" ]);
            $compiler->setOutputStyle(OutputStyle::COMPRESSED);
            
            $result = $compiler->compileString($result, $file)->getCSS();
        }
        
        return $result;
    }
    
    function fetchStyleSheet(): ?string
    {
        return $this->fetchResource("stylesheet.scss", true) ?? $this->fetchResource("stylesheet.css", true);
    }
    
    function fetchStaticResource(string $name): ?string
    {
        return $this->fetchResource("res/$name");
    }
    
    static function themepackFromDir(string $dirname): Themepack
    {
        $manifestFile = "$dirname/theme.yml";
        if(!file_exists($manifestFile))
            throw new Exceptions\NotThemeDirectoryException("Could not locate manifest at $dirname");
        
        $manifest = (object) chandler_parse_yaml($manifestFile);
        if(!isset($manifest->id) || !isset($manifest->version) || !isset($manifest->openvk_version) || !isset($manifest->metadata))
            throw new Exceptions\MalformedManifestException("Manifest is missing required information");
        
        if($manifest->openvk_version > Themepacks::THEMPACK_ENGINE_VERSION)
            throw new Exceptions\IncompatibleThemeException("Theme is built for newer OVK (themeEngine" . $manifest->openvk_version . ")");
        
        return new static($manifest->id, $manifest->version, (bool) ($manifest->inherit_master ?? true), (bool) ($manifest->override_templates ?? false), (bool) ($manifest->enabled ?? true), (object) $manifest->metadata);
    }
}
