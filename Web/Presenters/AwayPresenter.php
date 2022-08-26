<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use Chandler\Database\DatabaseConnection;

final class AwayPresenter extends OpenVKPresenter
{
    function renderAway(): void
    {
        $banned_link = DatabaseConnection::i()->getContext()->table("links_banned")->where("link", parse_url($this->queryParam("to", PHP_URL_HOST)))->fetch();

        if (!is_null($banned_link))
            $this->flashFail("err", tr("url_is_banned"), tr("url_is_banned_comment", OPENVK_ROOT_CONF["openvk"]["appearance"]["name"], $banned_link->reason ?: tr("url_is_banned_default_reason")));

        header("HTTP/1.0 302 Found");
        header("X-Robots-Tag: noindex, nofollow, noarchive");
        header("Location: " . $this->queryParam("to"));
        exit;
    }
}
