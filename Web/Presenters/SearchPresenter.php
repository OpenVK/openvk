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
        
        switch($type) {
            case "groups":
                $iterator = $this->clubs->find($query, $page);
                $count    = $this->clubs->getFoundCount($query);
                break;
            case "users":
                $iterator = $this->users->find($query)->page($page);
                $count    = $this->users->find($query)->size();
                break;
        }
        
        $this->template->iterator = iterator_to_array($iterator);
        $this->template->count    = $count;
        $this->template->type     = $type;
        $this->template->page     = $page;
    }
}
