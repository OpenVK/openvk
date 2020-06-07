<?php declare(strict_types=1);
namespace openvk\Web\Presenters;

final class AwayPresenter extends OpenVKPresenter
{
    function renderAway(): void
    {
        header("HTTP/1.0 302 Found");
        header("X-Robots-Tag: noindex, nofollow, noarchive");
        header("Location: " . $this->queryParam("to"));
        exit;
    }
}
