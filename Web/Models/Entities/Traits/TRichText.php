<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;

trait TRichText
{
    private function formatEmojis(string $text): string
    {
        $emojis = \Emoji\detect_emoji($text);
        foreach($emojis as $emoji) {
            $point  = strtolower($emoji["hex_str"]);
            $image  = "https://cdn.jsdelivr.net/npm/emojione-assets@4.5.0/png/32/$point.png";
            $image  = "<img src='$image' alt='$emoji[emoji]' ";
            $image .= "style='max-height:10pt; padding-left: 2pt; padding-right: 2pt; vertical-align: bottom;' />";
            
            $text = str_replace($emoji["emoji"], $image, $text);
        }
        
        return $text;
    }
    
    private function removeZalgo(string $text): string
    {
        return preg_replace("%[\x{0300}-\x{036F}]{3,}%Xu", "�", $text);
    }
    
    function getText(bool $html = true): string
    {
        $text = htmlentities($this->getRecord()->content, ENT_DISALLOWED | ENT_XHTML);
        if($html) {
            $rel  = $this->isAd() ? "sponsored" : "ugc";
            $text = preg_replace(
                "%((https?|ftp):\/\/(\S*?\.\S*?))([\s)\[\]{},;\"\':<]|\.\s|$)%",
                "<a href='$1' rel='$rel' target='_blank'>$3</a>$4",
                $text
            );
            $text = preg_replace("%@(id|club)([0-9]++) \(([\p{L} 0-9]+)\)%Xu", "[$1$2|$3]", $text);
            $text = preg_replace("%@(id|club)([0-9]++)%Xu", "[$1$2|@$1$2]", $text);
            $text = preg_replace("%\[(id|club)([0-9]++)\|([\p{L} 0-9@]+)\]%Xu", "<a href='/$1$2'>$3</a>", $text);
            $text = preg_replace("%(#([\p{L}_-]++[0-9]*[\p{L}_-]*))%Xu", "<a href='/feed/hashtag/$2'>$1</a>", $text);
            $text = $this->formatEmojis($text);
            $text = $this->removeZalgo($text);
            $text = nl2br($text);
        }
        
        return $text;
    }
}
