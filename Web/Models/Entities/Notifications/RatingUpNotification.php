<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\User;

final class RatingUpNotification extends HybridNotification
{
    protected $actionCode = 9603;

    public function __construct(User $receiver, User $sender, int $value, string $message)
    {
        parent::__construct($receiver, $receiver, $sender, time(), $value . " " . $message);
    }

    public function getSendParams(): array
    {
        return [];
    }

    public function getSendMethod(): string
    {
        return "messages.send";
    }
}
