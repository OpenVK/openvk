<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\{ActiveRow, Selection};
use openvk\Web\Models\Entities\BannedLink;

class BannedLinks
{
    private $context;
    private $bannedLinks;

    function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->bannedLinks = $this->context->table("links_banned");
    }

    function toBannedLink(?ActiveRow $ar): ?BannedLink
    {
        return is_null($ar) ? NULL : new BannedLink($ar);
    }

    function get(int $id): ?BannedLink
    {
        return $this->toBannedLink($this->bannedLinks->get($id));
    }

    function getByDomain(string $domain): ?Selection
    {
        return $this->bannedLinks->where("domain", $domain);
    }

    function isDomainBanned(string $domain): bool
    {
        return !is_null(DB::i()->getConnection()->query("SELECT * FROM `links_banned` WHERE `link` = '$domain' AND `regexp_rule` = ''")->fetch());
    }

    function check(string $url): ?array
    {
        $uri = preg_replace("(^https?://)", "", $url);
        $domain = preg_replace('/^www\./', '', parse_url($url)["host"]);

        $rulesForDomain = $this->getByDomain($domain);

        if (is_null($rulesForDomain))
            return NULL;

        $links = [];

        foreach($rulesForDomain as $rule)
        {
            $links[] = $this->get($rule->id);
        }

        $entries = [];

        foreach($links as $link)
        {
            if (preg_match($link->getRegexpRule(), $uri))
                $entries[] = $link->getId();
        }

        return $entries;
    }
}