<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\WikiPage;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class WikiPages
{
    private $context;
    private $wp;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->wp      = $this->context->table("wikipages");
    }
    
    private function toWikiPage(?ActiveRow $ar): ?WikiPage
    {
        return is_null($ar) ? NULL : new WikiPage($ar);
    }
    
    function get(int $id): ?WikiPage
    {
        return $this->toWikiPage($this->wp->get($id));
    }
    
    function getByOwnerAndVID(int $owner, int $note): ?WikiPage
    {
        $wp = (clone $this->wp)->where(['owner' => $owner, 'virtual_id' => $note])->fetch();
        return $this->toWikiPage($wp);
    }
    
    function getByOwnerAndTitle(int $owner, string $title): ?WikiPage
    {
        $wp = (clone $this->wp)->where(['owner' => $owner, 'title' => $title])->fetch();
        return $this->toWikiPage($wp);
    }
}
