<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\{Clubs, WikiPages};
use openvk\Web\Models\Entities\WikiPage;

final class WikiPresenter extends OpenVKPresenter
{
    private $groups;
    private $pages;
    
    function __construct(Clubs $groups, WikiPages $pages)
    {
        $this->groups = $groups;
        $this->pages  = $pages;
        
        parent::__construct();
    }
    
    function renderView(int $owner, int $page)
    {
        $page = $this->pages->getByOwnerAndVID((int) $owner, (int) $page);
        if(!$page || !$page->getOwner()->containsWiki())
            $this->notFound();
        
        $this->template->oURL  = $page->getOwner()->getURL();
        $this->template->oName = $page->getOwner()->getCanonicalName();
        $this->template->title = $page->getTitle();
        $this->template->html  = $page->getText([
            
        ]);
    }
    
    function renderSource(int $owner, int $page)
    {
        $page = $this->pages->getByOwnerAndVID((int) $owner, (int) $page);
        if(!$page)
            $this->flashFail("err", tr("error"), tr("page_id_invalid"));
        
        $src = $page->getSource();
        header("Content-Type: text/plain");
        header("Content-Length: " . strlen($src));
        header("Pragma: no-cache");
        exit($src);
    }
    
    function renderEdit()
    {
        $this->assertUserLoggedIn();
        
        if(is_null($groupId = $this->queryParam("gid")))
            $this->flashFail("err", tr("error"), tr("group_id_invalid"));
        
        $group = $this->groups->get((int) $groupId);
        if(!$group)
            $this->flashFail("err", tr("error"), tr("group_id_invalid"));
        else if(!$group->canBeModifiedBy($this->user->identity))
            $this->flashFail("err", tr("error"), tr("access_error"));
        
        $page;
        $pageId = $this->queryParam("elid");
        $title  = $this->requestParam("title");
        if(!is_null($pageId) && $pageId > 0) {
            $page = $this->pages->getByOwnerAndVID($groupId * -1, (int) $pageId);
            if(!$page)
                $this->flashFail("err", tr("error"), tr("page_id_invalid"));
        } else if(!is_null($title)) {
            $page = $this->pages->getByOwnerAndTitle($groupId * -1, $title);
        } else {
            $this->flashFail("err", tr("error"), tr("page_id_invalid"));
        }
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            
            if($this->postParam("elid") == 0) {
                $page = new WikiPage;
                $page->setOwner($groupId * -1);
                $page->setTitle($title);
            }
            
            if($this->postParam("elid") != 0 && $title !== $page->getTitle()) {
                if(!is_null($this->pages->getByOwnerAndTitle($groupId * -1, $title)))
                    $this->flashFail("err", tr("error"), tr("article_already_exists"));
                
                $page->setTitle($title);
            }
            
            $page->setSource($this->postParam("source"));
            $page->save();
            
            $this->flash("succ", tr("succ"), tr("article_saved"));
            $this->redirect("/pages?gid=$groupId&title=" . rawurlencode($title));
            return;
        }
        
        if(!$page) {
            $this->template->form = (object) [
                "pId"    => 0,
                "gId"    => $groupId,
                "title"  => $title ?? "",
                "source" => "",
            ];
        } else {
            $this->template->form = (object) [
                "pId"    => $page->getVirtualId(),
                "gId"    => $groupId,
                "title"  => $page->getTitle(),
                "source" => $page->getSource(),
            ];
        }
    }
}
