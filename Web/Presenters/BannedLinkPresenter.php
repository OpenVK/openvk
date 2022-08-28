<?php declare(strict_types=1);
namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\BannedLink;
use openvk\Web\Models\Repositories\BannedLinks;

final class BannedLinkPresenter extends OpenVKPresenter
{
    function renderView(int $lid) {
        $this->template->link = (new BannedLinks)->get($lid);
        $this->template->to   = $this->queryParam("to");
    }
}
