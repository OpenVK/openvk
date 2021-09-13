<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Themes\Themepacks;
use openvk\Web\Models\Repositories\{Users, Managers};
use Chandler\Session\Session;

final class AboutPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    
    function renderIndex(): void
    {
        if(!is_null($this->user)) {
            header("HTTP/1.1 302 Found");
            header("Location: /id" . $this->user->id);
            exit;
        }
        
        if($_SERVER['REQUEST_URI'] == "/id0") {
            header("HTTP/1.1 302 Found");
            header("Location: /");
            exit;
        }
        
        $this->template->stats = (new Users)->getStatistics();
    }
    
    function renderRules(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "rules");
    }
    
    function renderHelp(): void
    {}
    
    function renderBB(): void
    {}
    
    function renderInvite(): void
    {
        $this->assertUserLoggedIn();
    }
    
    function renderDonate(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "donate");
    }
    
    function renderPrivacy(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "privacy");
    }
    
    function renderVersion(): void
    {
        $this->template->themes = Themepacks::i()->getAllThemes();
    }
    
    function renderLanguage(): void
    {
        if(!is_null($_GET['lg'])){
            Session::i()->set("lang", $_GET['lg']);
        }
    }

    function renderSandbox(): void
    {
        $this->template->manager = (new Managers)->get(4);
    }
}
