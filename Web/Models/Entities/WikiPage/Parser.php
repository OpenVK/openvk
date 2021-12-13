<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\WikiPage;
use openvk\Web\Models\Repositories\WikiPages;
use Netcarver\Textile;
use HTMLPurifier_Config;
use HTMLPurifier;

class Parser
{
    private $depth;
    private $page;
    private $repo;
    private $vars;
    private $ctx;
    
    private $entityNames = [
        "id"    => [0, "User", "get", "getAvatarURL"],
        "photo" => [1, "Photo", "getByOwnerAndVID", "getURL"],
        "video" => [1, "Video", "getByOwnerAndVID", "getThumbnailURL"],
        "note"  => [1, "Note", "getNoteById", NULL],
        "club"  => [0, "Group", "get", "getAvatarURL"],
        "wall"  => [1, "Post", "getPostById", NULL],
    ];
    
    const REFERENCE_SINGULAR = 0;
    const REFERENCE_DUAL     = 1;
    
    function __construct(WikiPage $page, WikiPages $repo, int &$counter, array $vars = [], array $ctx = []) {
        $this->depth = $counter;
        $this->page  = $page;
        $this->repo  = $repo;
        $this->vars  = $vars;
        $this->ctx   = $ctx;
    }
    
    private function getPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set("Attr.AllowedClasses", ["unbordered", "inline", "nonexistent"]);
        $config->set("Attr.DefaultInvalidImageAlt", "Unknown image");
        $config->set("AutoFormat.AutoParagraph", true);
        $config->set("AutoFormat.Linkify", true);
        $config->set("URI.Base", "//$_SERVER[SERVER_NAME]/");
        $config->set("URI.Munge", "/away.php?xinf=%n.%m:%r&css=%p&to=%s");
        $config->set("URI.MakeAbsolute", true);
        $config->set("HTML.Doctype", "XHTML 1.1");
        $config->set("HTML.TidyLevel", "heavy");
        $config->set("HTML.AllowedElements", [
            "div",
            "h3",
            "h4",
            "h5",
            "h6",
            "p",
            "i",
            "b",
            "a",
            "del",
            "ins",
            "sup",
            "sub",
            "table",
            "thead",
            "tbody",
            "tr",
            "td",
            "th",
            "img",
            "ul",
            "ol",
            "li",
            "hr",
            "br",
            "acronym",
            "blockquote",
            "cite",
        ]);
        $config->set("HTML.AllowedAttributes", [
            "table.summary",
            "td.abbr",
            "th.abbr",
            "a.href",
            "img.src",
            "img.alt",
            "img.style",
            "div.style",
            "div.title",
        ]);
        $config->set("CSS.AllowedProperties", [
            "float",
            "height",
            "width",
            "max-height",
            "max-width",
            "font-weight",
        ]);
        
        return new HTMLPurifier($config);;
    }
    
    private function resolveEntityURL(array $matches, bool $dual = false, bool $needImage = false): ?string
    {
        $descriptor = $this->entityNames[$matches[1]] ?? NULL;
        if($descriptor && $descriptor[0] === ((int) $dual)) {
            $repoClass = 'openvk\Web\Models\Repositories\\' . $descriptor[1] . 's';
            $repoInst  = new $repoClass;
            $entity    = $repoInst->{$descriptor[2]}(...array_map(function($x): int {
                return (int) $x;
            }, array_slice($matches, 2)));
            
            if($entity) {
                if($needImage) {
                    $serverUrl       = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
                    $thumbnailMethod = $descriptor[3];
                    if(!$thumbnailMethod)
                        return "$serverUrl/assets/packages/static/openvk/img/entity_nopic.png";
                    else
                        return $entity->{$thumbnailMethod}();
                } else {
                    if(in_array('openvk\Web\Models\Entities\ILinkable', class_implements($entity)))
                        return $entity->getOVKLink();
                }
            }
        }
        
        return NULL;
    }
    
    private function resolvePageLinks(): string
    {
        return preg_replace_callback('%\[\[((?:\p{L}\p{M}?|[ 0-9\-\'_\/#])+)(\|(\p{L}\p{M}?|[ 0-9\-\'_\/]))?\]\]%u', function(array $matches): string {
            $gid    = $this->page->getOwner()->getId() * -1;
            $page   = $this->repo->getByOwnerAndTitle($gid, $matches[1]);
            $title  = $matches[3] ?? $matches[1];
            $nTitle = htmlentities($title);
            if(!$page)
                return "\"(nonexistent)$nTitle\":/pages?elid=0&gid=" . ($gid * -1) . "&title=" . rawurlencode($title);
            else
                return "\"$nTitle\":/page" . $page->getPrettyId();
        }, $this->page->getSource());
    }
    
    private function parseVariables(): string
    {
        $html = $this->resolvePageLinks();
        return preg_replace_callback('%(?<!\\\\)\?(\$|#)([A-z_]|[A-z_][A-z_0-9]|(?:[A-z_][A-z_\-\\\'0-9]+[A-z_0-9]))\?%', function(array $matches): string {
            return (string) (($matches[1] === "$" ? $this->ctx : $this->vars)[$matches[2]] ?? "<b>Notice: Unknown variable $matches[2]</b><br/>");
        }, $html);
    }
    
    private function parseOvkTemplates(): string
    {
        $html = $this->parseVariables();
        
        if($this->counter < 5) {
            return preg_replace_callback('%{{Template:\-([0-9]++)_([0-9]++)\|?([^{}]++)}}%', function(array $matches): string {
                $params = [];
                [, $public, $page, $paramStr] = $matches;
                
                $tplPage = $this->repo->getByOwnerAndVID(-1 * $public, (int) $page);
                if(!$tplPage)
                    return "<b>Notice: No template at public$public/$page</b><br/>";
                
                foreach(explode("|", $paramStr) as $kvPair) {
                    $kvPair = explode("=", $kvPair);
                    if(sizeof($kvPair) != 2)
                        continue;
                    
                    $params[$kvPair[0]] = $kvPair[1];
                }
                bdump($params);
                $parser = new Parser($tplPage, $this->repo, $this->depth, $params, $this->ctx);
                return $parser->asHTML();
            }, $html);
        } else {
            return "<b>Notice: Refusing to include template due to high indirection level (6)</b><br/>";
        }
    }
    
    private function parseTextile(): string
    {
        return (new Textile\Parser)->parse($this->parseOvkTemplates());
    }
    
    private function parseOvkIncludes(): string
    {
        $html = new \DOMDocument();
        $html->loadHTML("<?xml encoding=\"UTF-8\">" . $this->parseTextile());
        foreach($html->getElementsByTagName("a") as $link) {
            $href = $link->getAttribute("href");
            if(preg_match('%^#([a-z]++)(\-?[0-9]++)_([0-9]++)#$%', $href, $matches))
                $link->setAttribute("href", $this->resolveEntityURL($matches, true, false) ?? "unknown");
            else if(preg_match('%^#([a-z]++)(\-?[0-9]++)#$%', $href, $matches))
                $link->setAttribute("href", $this->resolveEntityURL($matches, false, false) ?? "unknown");
        }
        
        foreach($html->getElementsByTagName("img") as $pic) {
            $src = $pic->getAttribute("src");
            if(preg_match('%^#([a-z]++)(\-?[0-9]++)_([0-9]++)#$%', $src, $matches))
                $pic->setAttribute("src", $this->resolveEntityURL($matches, true, true) ?? "unknown");
            else if(preg_match('%^#([a-z]++)(\-?[0-9]++)#$%', $src, $matches))
                $pic->setAttribute("src", $this->resolveEntityURL($matches, false, true) ?? "unknown");
        }
        
        return $html->saveHTML();
    }
    
    function asHTML(): string
    {
        $purifier = $this->getPurifier();
        return $purifier->purify($this->parseOvkIncludes());
    }
}
