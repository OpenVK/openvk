<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\Postable;
use openvk\Web\Models\Entities\User;

final class MentionNotification extends Notification
{
    protected $actionCode = 4;

    public function __construct(User $recipient, $mentioner, Postable $discussionHost, string $quote = "")
    {
        parent::__construct($recipient, $mentioner, $discussionHost, time(), $quote);
    }
}
