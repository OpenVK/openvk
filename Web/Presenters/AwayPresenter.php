<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\BannedLinks;
use openvk\Web\Models\Entities\BannedLink;

final class AwayPresenter extends OpenVKPresenter
{
    public function renderAway(): void
    {
        $redirTo = $this->queryParam("to");
        if (OPENVK_ROOT_CONF["openvk"]["preferences"]["susLinks"]["warnings"]) {
            $checkBanEntries = (new BannedLinks())->check($redirTo);
            if (sizeof($checkBanEntries) > 0) {
                $this->pass("openvk!Away->view", $checkBanEntries[0]);
            }
        }

        if (isset(OPENVK_ROOT_CONF["openvk"]["mirrors"])) {
            $uri = str_replace(["https://", "http://"], "", $redirTo);
            $domainTo = explode("/", $uri)[0];
            $isMirror = in_array(str_replace("www.", "", $domainTo), OPENVK_ROOT_CONF["openvk"]["mirrors"]);
            if ($isMirror) {
                $currentDomain = $_SERVER["SERVER_NAME"];
                $redirTo = str_replace($domainTo, $currentDomain, $redirTo);
            }
        }

        header("HTTP/1.0 302 Found");
        header("X-Robots-Tag: noindex, nofollow, noarchive");
        header("Location: " . rawurldecode($redirTo));
        exit;
    }

    public function renderView(int $lid)
    {
        $this->template->link = (new BannedLinks())->get($lid);

        if (!$this->template->link) {
            $this->notFound();
        }

        $this->template->to   = $this->queryParam("to");
    }
}
