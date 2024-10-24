<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;
use openvk\Web\Models\Repositories\{Users, Clubs};
use Wkhooy\ObsceneCensorRus;

trait TRichText
{
    private function formatEmojis(string $text): string
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        if(iconv_strlen($this->getRecord()->{$contentColumn}) > OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["emojiProcessingLimit"])
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
            "%(([A-z]++):\/\/(\S*?\.\S*?))([\s)\[\]{},\"\'<]|\.\s|$)%",
            (function (array $matches): string {
                $href = str_replace("#", "&num;", $matches[1]);
                $href = rawurlencode(str_replace(";", "&#59;", $href));
                $link = str_replace("#", "&num;", $matches[3]);
                $link = str_replace(";", "&#59;", $link);
                $rel  = $this->isAd() ? "sponsored" : "ugc";
                
                return "<a href='/away.php?to=$href' rel='$rel' target='_blank'>$link</a>" . htmlentities($matches[4]);
            }),
            $text
        );
    }
    
    private function removeZalgo(string $text): string
    {
        return preg_replace("%\p{M}{3,}%Xu", "", $text);
    }
    
    function resolveMentions(array $skipUsers = []): \Traversable
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        $text = $this->getRecord()->{$contentColumn};
        $text = preg_replace("%@([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
        $text = preg_replace("%([\n\r\s]|^)(@([A-Za-z0-9]++))%Xu", "$1[$3|@$3]", $text);
        
        $resolvedUsers = $skipUsers;
        $resolvedClubs = [];
        preg_match_all("%\[([A-Za-z0-9]++)\|((?:[\p{L&}\p{Lo} 0-9@]\p{Mn}?)++)\]%Xu", $text, $links, PREG_PATTERN_ORDER);
        foreach($links[1] as $link) {
            if(preg_match("%^id([0-9]++)$%", $link, $match)) {
                $uid = (int) $match[1];
                if(in_array($uid, $resolvedUsers))
                    continue;
                
                $resolvedUsers[] = $uid;
                $maybeUser = (new Users)->get($uid);
                if($maybeUser)
                    yield $maybeUser;
            } else if(preg_match("%^(?:club|public|event)([0-9]++)$%", $link, $match)) {
                $cid = (int) $match[1];
                if(in_array($cid, $resolvedClubs))
                    continue;
    
                $resolvedClubs[] = $cid;
                $maybeClub = (new Clubs)->get($cid);
                if($maybeClub)
                    yield $maybeClub;
            } else {
                $maybeUser = (new Users)->getByShortURL($link);
                if($maybeUser) {
                    $uid = $maybeUser->getId();
                    if(in_array($uid, $resolvedUsers))
                        continue;
                    else
                        $resolvedUsers[] = $uid;
                    
                    yield $maybeUser;
                    continue;
                }
                
                $maybeClub = (new Clubs)->getByShortURL($link);
                if($maybeClub) {
                    $cid = $maybeClub->getId();
                    if(in_array($cid, $resolvedClubs))
                        continue;
                    else
                        $resolvedClubs[] = $cid;
    
                    yield $maybeClub;
                }
            }
        }
    }
    
    function getText(bool $html = true): string
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        
        $text = htmlspecialchars($this->getRecord()->{$contentColumn}, ENT_DISALLOWED | ENT_XHTML);
        $proc = iconv_strlen($this->getRecord()->{$contentColumn}) <= OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["processingLimit"];
        if($html) {
            if($proc) {
                $text = $this->formatLinks($text);
                $text = preg_replace("%@([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
                $text = preg_replace("%([\n\r\s]|^)(@([A-Za-z0-9]++))%Xu", "$1[$3|@$3]", $text);
                $text = preg_replace("%\[([A-Za-z0-9]++)\|((?:[\p{L&}\p{Lo} 0-9@]\p{Mn}?)++)\]%Xu", "<a href='/$1'>$2</a>", $text);
                $text = preg_replace_callback("%([\n\r\s]|^)(\#([\p{L}_0-9][\p{L}_0-9\(\)\-\']+[\p{L}_0-9\(\)]|[\p{L}_0-9]{1,2}))%Xu", function($m) {
                    $slug = rawurlencode($m[3]);
                    
                    return "$m[1]<a href='/search?section=posts&q=%23$slug'>$m[2]</a>";
                }, $text);
                
                $text = $this->formatEmojis($text);
            }
            
            $text = $this->removeZalgo($text);
            $text = nl2br($text);
        } else {
            $text = str_replace("\r\n","\n", $text);
        }
        
        if(OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["christian"])
            ObsceneCensorRus::filterText($text);
        
        return $text;
    }
}
