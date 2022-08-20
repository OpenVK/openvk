<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Application;
use openvk\Web\Models\Repositories\Applications;

final class AppsPresenter extends OpenVKPresenter
{
    private $apps;
    
    function __construct(Applications $apps)
    {
        $this->apps = $apps;
        
        parent::__construct();
    }
    
    function renderPlay(int $app): void
    {
        $this->assertUserLoggedIn();
    
        $app = $this->apps->get($app);
        if(!$app || !$app->isEnabled())
            $this->notFound();
    
        $this->template->id     = $app->getId();
        $this->template->name   = $app->getName();
        $this->template->desc   = $app->getDescription();
        $this->template->origin = $app->getOrigin();
        $this->template->url    = $app->getURL();
        $this->template->owner  = $app->getOwner();
        $this->template->news   = $app->getNote();
        $this->template->perms  = $app->getPermissions($this->user->identity);
    }
    
    function renderUnInstall(): void
    {
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();
    
        $app = $this->apps->get((int) $this->queryParam("app"));
        if(!$app)
            $this->flashFail("err", "Invalid ID", "Invalid application ID supplied"); // TODO trans
        
        $app->uninstall($this->user->identity);
        $this->flashFail("succ", "Application uninstalled", "You can reinstall it by launching again."); // TODO trans
    }
    
    function renderEdit(): void
    {
        $this->assertUserLoggedIn();
        
        $app = NULL;
        if($this->queryParam("act") !== "create") {
            if(empty($this->queryParam("app")))
                $this->flashFail("err", "Invalid ID", "Invalid application ID supplied"); // TODO trans
    
            $app = $this->apps->get((int) $this->queryParam("app"));
            if(!$app)
                $this->flashFail("err", "Invalid ID", "Invalid application ID supplied"); // TODO trans
    
            if($app->getOwner()->getId() != $this->user->identity->getId())
                $this->flashFail("err", "Invalid ID", "Invalid application ID supplied"); // TODO trans
        }
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!$app) {
                $app = new Application;
                $app->setOwner($this->user->id);
            }
            
            if(!filter_var($this->postParam("url"), FILTER_VALIDATE_URL))
                $this->flashFail("err", "Invalid URL", "Invalid URL supplied"); // TODO trans
    
            if(isset($_FILES["ava"]) && $_FILES["ava"]["size"] > 0) {
                if(($res = $app->setAvatar($_FILES["ava"])) !== 0)
                    $this->flashFail("err", "Invalid avatar", "E$res"); // TODO trans
            }
            
            if(empty($this->postParam("note"))) {
                $app->setNoteLink(NULL);
            } else {
                if(!$app->setNoteLink($this->postParam("note")))
                    $this->flashFail("err", "Invalid note link", "lll"); // TODO trans
            }
            
            $app->setName($this->postParam("name"));
            $app->setDescription($this->postParam("desc"));
            $app->setAddress($this->postParam("url"));
            if($this->postParam("enable") === "on")
                $app->enable();
            else
                $app->disable(); # no need to save since enable/disable will call save() internally
            
            $this->redirect("/editapp?act=edit&app=" . $app->getId()); # will exit here
        }
        
        if(!is_null($app)) {
            $this->template->create = false;
            $this->template->id     = $app->getId();
            $this->template->name   = $app->getName();
            $this->template->desc   = $app->getDescription();
            $this->template->coins  = $app->getBalance();
            $this->template->origin = $app->getOrigin();
            $this->template->url    = $app->getURL();
            $this->template->note   = $app->getNoteLink();
            $this->template->on     = $app->isEnabled();
        } else {
            $this->template->create = true;
        }
    }
    
    function renderList(): void
    {
        $this->assertUserLoggedIn();
        
        $act = $this->queryParam("act");
        if(!in_array($act, ["list", "installed", "dev"]))
            $act = "installed";
        
        $page = (int) ($this->queryParam("p") ?? 1);
        if($act == "list") {
            $apps  = $this->apps->getList($page);
            $count = $this->apps->getListCount();
        } else if($act == "installed") {
            $apps  = $this->apps->getInstalled($this->user->identity, $page);
            $count = $this->apps->getInstalledCount($this->user->identity);
        } else if($act == "dev") {
            $apps  = $this->apps->getByOwner($this->user->identity, $page);
            $count = $this->apps->getOwnCount($this->user->identity);
        }
        
        $this->template->act      = $act;
        $this->template->iterator = $apps;
        $this->template->count    = $count;
        $this->template->page     = $page;
    }
}