<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Entities\Club;
use Chandler\Database\DatabaseConnection;

final class SearchPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    
    function __construct(Users $users, Clubs $clubs)
    {
        $this->users = $users;
        $this->clubs = $clubs;
        
        parent::__construct();
    }
    
    function renderIndex(): void
    {
        $query = $this->queryParam("query") ?? "";
        $type  = $this->queryParam("type") ?? "users";
        $page  = (int) ($this->queryParam("p") ?? 1);
        
        // https://youtu.be/pSAWM5YuXx8
        
        $repos = [ "groups" => "clubs", "users" => "users" ];
        $repo  = $repos[$type] or $this->throwError(400, "Bad Request", "Invalid search entity $type.");
        
        $results  = $this->{$repo}->find($query);
        $iterator = $results->page($page);
        $count    = $results->size();
        
        $this->template->iterator = iterator_to_array($iterator);
        $this->template->count    = $count;
        $this->template->type     = $type;
        $this->template->page     = $page;
    }
}
