<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\{Users, Managers};
use Composer\Factory;
use Composer\IO\NullIO;
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
    {}
    
    function renderDonate(): void
    {}
    
    function renderPrivacy(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "privacy");
    }
    
    function renderVersion(): void
    {
        //$composerFactory = new Factory();
        //$composer        = $composerFactory->createComposer(new NullIO(), OPENVK_ROOT . "/composer.json", false);
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
