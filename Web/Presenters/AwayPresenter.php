<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\BannedLinks;

final class AwayPresenter extends OpenVKPresenter
{
    function renderAway(): void
    {
        $link_rule = (new BannedLinks)->getByDomain(parse_url($this->queryParam("to", PHP_URL_HOST))["host"]);

        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["susLinks"]["allowTransition"]) {
            if (!is_null($link_rule))
                $this->pass("openvk!BannedLink->view", $link_rule->getId());
        }

        header("HTTP/1.0 302 Found");
        header("X-Robots-Tag: noindex, nofollow, noarchive");
        header("Location: " . $this->queryParam("to"));
        exit;
    }
}
