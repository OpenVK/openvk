<?php declare(strict_types=1);
namespace openvk\Web\Presenters;

final class NotificationPresenter extends OpenVKPresenter
{
    function renderFeed(): void
    {
        $this->assertUserLoggedIn();
        
        $archive = $this->queryParam("act") === "archived";
        $this->template->mode     = $archive ? "archived" : "new";
        $this->template->page     = (int) ($this->queryParam("p") ?? 1);
        $this->template->iterator = iterator_to_array($this->user->identity->getNotifications($this->template->page, $archive));
        $this->template->count    = $this->user->identity->getNotificationsCount($archive);
        
        $this->user->identity->updateNotificationOffset();
        $this->user->identity->save();
    }
}
