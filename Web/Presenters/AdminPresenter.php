<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Users, Clubs};

final class AdminPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    
    function __construct(Users $users, Clubs $clubs)
    {
        $this->users = $users;
        $this->clubs = $clubs;
        
        parent::__construct();
    }
    
    private function searchResults(object $repo, &$count)
    {
        $query = $this->queryParam("q") ?? "";
        $page  = (int) ($this->queryParam("p") ?? 1);
        
        $count = $repo->getFoundCount($query);
        return $repo->find($query, $page);
    }
    
    function onStartup(): void
    {
        parent::onStartup();
        
        $this->assertPermission("admin", "access", -1);
    }
    
    function renderIndex(): void
    {
        
    }
    
    function renderUsers(): void
    {
        $this->template->users = $this->searchResults($this->users, $this->template->count);
    }
    
    function renderUser(int $id): void
    {
        $user = $this->users->get($id);
        if(!$user)
            $this->notFound();
        
        $this->template->user = $user;
        
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;
        
        switch($_POST["act"] ?? "info") {
            default:
            case "info":
                break;
            
            
        }
    }
    
    function renderClubs(): void
    {
        $this->template->clubs = $this->searchResults($this->clubs, $this->template->count);
    }
    
    function renderClub(int $id): void
    {
        $club = $this->clubs->get($id);
        if(!$club)
            $this->notFound();
        
        $this->template->club = $club;
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            
        }
    }
    
    function renderFiles(): void
    {
        
    }
    
    function renderQuickBan(int $id): void
    {
        $user = $this->users->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->ban($this->queryParam("reason"));
        exit(json_encode([ "reason" => $this->queryParam("reason") ]));
    }
    
    function renderQuickWarn(int $id): void
    {
        $user = $this->users->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->adminNotify("⚠️ " . $this->queryParam("message"));
        exit(json_encode([ "message" => $this->queryParam("message") ]));
    }
}
