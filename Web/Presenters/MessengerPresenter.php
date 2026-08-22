<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Signaling\SignalManager;
use openvk\Web\Events\NewMessageEvent;
use openvk\Web\Models\Repositories\{Users, Clubs, Messages};
use openvk\Web\Util\IMBroker;

final class MessengerPresenter extends OpenVKPresenter
{
    private $signaler;
    protected $presenterName = "messenger";

    public function renderIndex(): void
    {
        $this->assertUserLoggedIn();

        $im = IMBroker::i();
        $isAvailable = $im->isEnabled() && $im->pingLP();

        $this->template->imAvailable = $isAvailable;

        // #КакаоПрокакалось
    }
}
