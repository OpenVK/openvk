<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Clubs;

final class UnknownTextRouteStrategyPresenter extends OpenVKPresenter
{
    function renderDelegate(string $data): void
    {
        if(strlen($data) >= 2) {
            $user = (new Users)->getByShortURL($data);
            if($user)
                $this->pass("openvk!User->view", $user->getId());
            $club = (new Clubs)->getByShortURL($data);
            if($club)
                $this->pass("openvk!Group->view", "public", $club->getId());
        }
        
        $this->notFound();
    }
}
