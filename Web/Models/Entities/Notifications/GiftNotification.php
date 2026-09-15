<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\{User, Gift};

final class GiftNotification extends HybridNotification
{
    protected $actionCode = 9601;

    public function __construct(User $receiver, User $sender, Gift $gift, ?int $id)
    {
        parent::__construct($receiver, $gift, $sender, time(), (string) $id);
    }

    public function getSendParams(): array
    {
        return [
            "attachment" => "gift" . $this->getRecipient()->getRealId() . "_" . $this->data,
        ];
    }

    public function getSendMethod(): string
    {
        return "messages.send";
    }
}
