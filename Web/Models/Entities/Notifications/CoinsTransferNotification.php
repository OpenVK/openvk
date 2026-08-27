<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\User;

final class CoinsTransferNotification extends HybridNotification
{
    protected $actionCode = 9602;

    public function __construct(User $receiver, User $sender, int $value, string $message)
    {
        parent::__construct($receiver, $receiver, $sender, time(), $value . " " . $message);
    }

    public function getSendParams(): array
    {
        $p1 = explode(" ", $this->data, 2);

        return [
            "action_type" => "coins_transfer",
            "action_mid"  => $p1[0],
            "action_text" => $p1[1],
            "message"     => "sent you " . $p1[0] . " voices",
        ];
    }
}
