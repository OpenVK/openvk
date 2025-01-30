<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\{ActiveRow, Selection};
use openvk\Web\Models\Entities\BannedLink;

class BannedLinks
{
    private $context;
    private $bannedLinks;

    public function __construct()
    {
        $this->context = DB::i()->getContext();
        $this->bannedLinks = $this->context->table("links_banned");
    }

    public function toBannedLink(?ActiveRow $ar): ?BannedLink
    {
        return is_null($ar) ? null : new BannedLink($ar);
    }

    public function get(int $id): ?BannedLink
    {
        return $this->toBannedLink($this->bannedLinks->get($id));
    }

    public function getList(?int $page = 1): \Traversable
    {
        foreach ($this->bannedLinks->order("id DESC")->page($page, OPENVK_DEFAULT_PER_PAGE) as $link) {
            yield new BannedLink($link);
        }
    }

    public function getCount(int $page = 1): int
    {
        return sizeof($this->bannedLinks->fetch());
    }

    public function getByDomain(string $domain): ?Selection
    {
        return $this->bannedLinks->where("domain", $domain);
    }

    public function isDomainBanned(string $domain): bool
    {
        return sizeof($this->bannedLinks->where(["link" => $domain, "regexp_rule" => ""])) > 0;
    }

    public function genLinks($rules): \Traversable
    {
        foreach ($rules as $rule) {
            yield $this->get($rule->id);
        }
    }

    public function genEntries($links, $uri): \Traversable
    {
        foreach ($links as $link) {
            if (preg_match($link->getRegexpRule(), $uri)) {
                yield $link->getId();
            }
        }
    }

    public function check(string $url): ?array
    {
        $uri = strstr(str_replace(["https://", "http://"], "", $url), "/", true);
        $domain = str_replace("www.", "", $uri);
        $rules = $this->getByDomain($domain);

        if (is_null($rules)) {
            return null;
        }

        return iterator_to_array($this->genEntries($this->genLinks($rules), $uri));
    }
}
