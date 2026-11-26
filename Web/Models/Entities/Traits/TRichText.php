<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Traits;

use openvk\Web\Models\Repositories\{Users, Clubs};
use Wkhooy\ObsceneCensorRus;

trait TRichText
{
    private function formatEmojis(string $text): string
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        if (iconv_strlen($this->getRecord()->{$contentColumn}) > OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["emojiProcessingLimit"]) {
            return $text;
        }

        $emojis   = \Emoji\detect_emoji($text);
        $replaced = []; # OVK-113
        foreach ($emojis as $emoji) {
            $point = strtoupper(bin2hex(mb_convert_encoding($emoji["emoji"], 'UTF-16BE', 'UTF-8')));
            if (in_array($point, $replaced)) {
                continue;
            } else {
                $replaced[] = $point;
            }

            $image = "<div class='emoji emoji_$point'>$emoji[emoji]</div>";

            $text = str_replace($emoji["emoji"], $image, $text);
        }

        return $text;
    }

    private function escapeHtml(string $text, int $extraFlags = 0): string
    {
        return htmlspecialchars($text, ENT_DISALLOWED | ENT_XHTML | $extraFlags);
    }

    private function formatLink(string $rawUrl, string $hostNPath, string $terminator, string $serverName, string $rel): string
    {
        $domainNPath  = explode("/", $hostNPath, 2);
        $isSameDomain = $serverName !== "" && str_replace("www.", "", strtolower($domainNPath[0])) === $serverName;
        $href         = $isSameDomain
            ? $this->escapeHtml($domainNPath[1] ?? "", ENT_QUOTES)
            : "away.php?to=" . rawurlencode($rawUrl);

        $link = $this->escapeHtml(str_replace("\r", "", $rawUrl)); // clears caret return that triggered <br> to be spawned

        return "<a href='/$href' rel='$rel' target='_blank'>$link</a>" . $this->escapeHtml($terminator);
    }

    private function formatLinks(string $text): string
    {
        $pattern = "%(([A-Za-z]++):\/\/([^\s<>\"\'()\[\]{}]*?\.[^\s<>\"\']*?))([\s)\[\]{},\"\'<>]|\.\s|$)%";
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return $this->escapeHtml($text);
        }

        $serverName = str_replace("www.", "", strtolower($_SERVER["SERVER_NAME"] ?? ""));
        $rel        = $this->isAd() ? "sponsored" : "ugc";
        $result     = "";
        $offset     = 0;

        foreach ($matches as $match) {
            $matchStart = $match[0][1];
            $result    .= $this->escapeHtml(substr($text, $offset, $matchStart - $offset));
            $result    .= $this->formatLink($match[1][0], $match[3][0], $match[4][0], $serverName, $rel);
            $offset     = $matchStart + strlen($match[0][0]);
        }

        return $result . $this->escapeHtml(substr($text, $offset));
    }

    private function removeZalgo(string $text): string
    {
        return preg_replace("%\p{M}{3,}%Xu", "", $text);
    }

    public function resolveMentions(array $skipUsers = []): \Traversable
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        $text = $this->getRecord()->{$contentColumn};
        $text = preg_replace("%@([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
        $text = preg_replace("%\*([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
        $text = preg_replace("%([\n\r\s]|^)(@([A-Za-z0-9]++))%Xu", "$1[$3|@$3]", $text);

        $resolvedUsers = $skipUsers;
        $resolvedClubs = [];
        preg_match_all("%\[([A-Za-z0-9]++)\|((?:[\p{L&}\p{Lo} 0-9@]\p{Mn}?)++)\]%Xu", $text, $links, PREG_PATTERN_ORDER);
        foreach ($links[1] as $link) {
            if (preg_match("%^id([0-9]++)$%", $link, $match)) {
                $uid = (int) $match[1];
                if (in_array($uid, $resolvedUsers)) {
                    continue;
                }

                $resolvedUsers[] = $uid;
                $maybeUser = (new Users())->get($uid);
                if ($maybeUser) {
                    yield $maybeUser;
                }
            } elseif (preg_match("%^(?:club|public|event)([0-9]++)$%", $link, $match)) {
                $cid = (int) $match[1];
                if (in_array($cid, $resolvedClubs)) {
                    continue;
                }

                $resolvedClubs[] = $cid;
                $maybeClub = (new Clubs())->get($cid);
                if ($maybeClub) {
                    yield $maybeClub;
                }
            } else {
                $maybeUser = (new Users())->getByShortURL($link);
                if ($maybeUser) {
                    $uid = $maybeUser->getId();
                    if (in_array($uid, $resolvedUsers)) {
                        continue;
                    } else {
                        $resolvedUsers[] = $uid;
                    }

                    yield $maybeUser;
                    continue;
                }

                $maybeClub = (new Clubs())->getByShortURL($link);
                if ($maybeClub) {
                    $cid = $maybeClub->getId();
                    if (in_array($cid, $resolvedClubs)) {
                        continue;
                    } else {
                        $resolvedClubs[] = $cid;
                    }

                    yield $maybeClub;
                }
            }
        }
    }

    public function getText(bool $html = true): string
    {
        $contentColumn = property_exists($this, "overrideContentColumn") ? $this->overrideContentColumn : "content";
        $rawText       = (string) $this->getRecord()->{$contentColumn};
        $proc          = iconv_strlen($rawText) <= OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["processingLimit"];

        if ($html) {
            if ($proc) {
                $text = $this->formatLinks($rawText);
                // Mentions: @user, @user (name), [id1|name]
                $text = preg_replace("%@([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
                $text = preg_replace("%\*([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
                $text = preg_replace("%([\n\r\s]|^)(@([A-Za-z0-9]++))%Xu", "$1[$3|@$3]", $text);
                $text = preg_replace("%\[([A-Za-z0-9]++)\|((?:[\p{L&}\p{Lo} 0-9\.\-\`\'@]\p{Mn}?)++)\]%Xu", "<a href='/$1'>$2</a>", $text);
                $text = preg_replace_callback("%([\n\r\s]|^)(\#([\p{L}_0-9][\p{L}_0-9\(\)\-\']+[\p{L}_0-9\(\)]|[\p{L}_0-9]{1,2}))%Xu", function ($m) {
                    $slug = rawurlencode($m[3]);

                    return "$m[1]<a href='/search?section=posts&q=%23$slug'>$m[2]</a>";
                }, $text);

                $text = $this->formatEmojis($text);
            } else {
                $text = $this->escapeHtml($rawText);
            }

            $text = $this->removeZalgo($text);
            $text = nl2br($text);
        } else {
            $text = $this->escapeHtml($rawText);
            $text = preg_replace("%@([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
            $text = preg_replace("%\*([A-Za-z0-9]++) \(((?:[\p{L&}\p{Lo} 0-9]\p{Mn}?)++)\)%Xu", "[$1|$2]", $text);
            $text = preg_replace("%([\n\r\s]|^)(@([A-Za-z0-9]++))%Xu", "$1[$3|@$3]", $text);
            $text = str_replace("\r\n", "\n", $text);
        }

        if (OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["christian"]) {
            ObsceneCensorRus::filterText($text);
        }

        return $text;
    }
}
