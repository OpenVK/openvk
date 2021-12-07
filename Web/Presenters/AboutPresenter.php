<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Themes\Themepacks;
use openvk\Web\Models\Repositories\{Users, Managers};
use openvk\Web\Util\Localizator;
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
        $this->template->languages = getLanguages();
    }
    
    function renderLanguage(): void
    {
        $this->template->languages = getLanguages();
        
        if(!is_null($_GET['lg'])){
            $this->assertNoCSRF();
            setLanguage($_GET['lg']);
        }
    }

    function renderExportJSLanguage($lg = NULL): void
    {
        $localizer = Localizator::i();
        $lang      = $lg;
        if(is_null($lg))
            $this->throwError(404, "Not found", "Language is not found");
        header("Content-Type: application/javascript");
        echo "window.lang = " . json_encode($localizer->export($lang)) . ";"; // привет хардкод :DDD
        exit;
    }

    function renderSandbox(): void
    {
        $this->template->languages = getLanguages();
    }
}
