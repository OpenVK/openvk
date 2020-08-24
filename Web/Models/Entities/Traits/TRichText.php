<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;

trait TRichText
{
    private function formatEmojis(string $text): string
    {
        if(iconv_strlen($this->getRecord()->content) > OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["emojiProcessingLimit"])
            return $text;
        
        $emojis   = \Emoji\detect_emoji($text);
        $replaced = []; # OVK-113
        foreach($emojis as $emoji) {
            $point = explode("-", strtolower($emoji["hex_str"]))[0];
            if(in_array($point, $replaced))
                continue;
            else
                $replaced[] = $point;
            
            $image  = "https://abs.twimg.com/emoji/v2/72x72/$point.png";
            $image  = "<img src='$image' alt='$emoji[emoji]' ";
            $image .= "style='max-height:12px; padding-left: 2pt; padding-right: 2pt; vertical-align: bottom;' />";
            
            $text = str_replace($emoji["emoji"], $image, $text);
        }
        
        return $text;
    }
	
	private function formatLinks(string &$text): string
	{
		return preg_replace_callback(
			"%(([A-z]++):\/\/(\S*?\.\S*?))([\s)\[\]{},;\"\'<]|\.\s|$)%",
			(function (array $matches): string {
				$href = str_replace("#", "&num;", $matches[1]);
				$link = str_replace("#", "&num;", $matches[3]);
				$rel  = $this->isAd() ? "sponsored" : "ugc";
				
				return "<a href='$href' rel='$rel' target='_blank'>$link</a>" . htmlentities($matches[4]);
			}),
			$text
		);
	}
    
    private function removeZalgo(string $text): string
    {
        return preg_replace("%[\x{0300}-\x{036F}]{3,}%Xu", "�", $text);
    }
    
    function getText(bool $html = true): string
    {
        $text = htmlentities($this->getRecord()->content, ENT_DISALLOWED | ENT_XHTML);
        $proc = iconv_strlen($this->getRecord()->content) <= OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["processingLimit"];
        if($html) {
            if($proc) {
                $rel  = $this->isAd() ? "sponsored" : "ugc";
                $text = $this->formatLinks($text);
                $text = preg_replace("%@(id|club)([0-9]++) \(([\p{L} 0-9]+)\)%Xu", "[$1$2|$3]", $text);
                $text = preg_replace("%@(id|club)([0-9]++)%Xu", "[$1$2|@$1$2]", $text);
                $text = preg_replace("%\[(id|club)([0-9]++)\|([\p{L} 0-9@]+)\]%Xu", "<a href='/$1$2'>$3</a>", $text);
                $text = preg_replace("%(#([\p{L}_-]++[0-9]*[\p{L}_-]*))%Xu", "<a href='/feed/hashtag/$2'>$1</a>", $text);
                $text = $this->formatEmojis($text);
            }
            
            $text = $this->removeZalgo($text);
            $text = nl2br($text);
        }
        
        return $text;
    }
}
