<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use HTMLPurifier_Config;
use HTMLPurifier;

class Note extends Postable
{
    protected $tableName = "notes";
    
    protected function renderHTML(): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set("Attr.AllowedClasses", []);
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
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($this->getRecord()->source);
    }
    
    function getName(): string
    {
        return $this->getRecord()->name;
    }
    
    function getPreview(int $length = 25): string
    {
        return ovk_proc_strtr(strip_tags($this->getRecord()->source), $length);
    }
    
    function getText(): string
    {
        $cached = $this->getRecord()->cached_content;
        if(!$cached) {
            $cached = $this->renderHTML();
            $this->setCached_Content($cached);
            $this->save();
        }
        
        return $cached;
    }
}
