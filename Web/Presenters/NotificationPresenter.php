<?php declare(strict_types=1);
namespace openvk\Web\Presenters;

final class NotificationPresenter extends OpenVKPresenter
{
    protected $presenterName = "notification";

    function renderFeed(): void
    {
        $this->assertUserLoggedIn();

        $archive = $this->queryParam("act") === "archived";
        $count   = $this->user->identity->getNotificationsCount($archive);

        if($count == 0 && $this->queryParam("act") == NULL) {
            $mode = "archived";
            $archive = true;
        } else {
            $mode = $archive ? "archived" : "new";
        }

        $this->template->mode     = $mode;
        $this->template->page     = (int) ($this->queryParam("p") ?? 1);
        $this->template->iterator = iterator_to_array($this->user->identity->getNotifications($this->template->page, $archive));
        $this->template->count    = $count;
        
        $this->user->identity->updateNotificationOffset();
        $this->user->identity->save();
    }
}
