<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\{User, Gift};

final class GiftNotification extends Notification
{
    protected $actionCode = 9601;

    public function __construct(User $receiver, User $sender, Gift $gift, ?string $comment)
    {
        parent::__construct($receiver, $gift, $sender, time(), $comment ?? "");
    }
}
