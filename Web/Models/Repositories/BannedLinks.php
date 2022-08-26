<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection as DB;
use Nette\Database\Table\ActiveRow;
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

    function getByDomain(string $domain): ?BannedLink
    {
        return $this->toBannedLink($this->bannedLinks->where("link", $domain)->fetch());
    }
}