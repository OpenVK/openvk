<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Gift};

final class CoinsTransferNotification extends Notification
{
    protected $actionCode = 9602;
    
    function __construct(User $receiver, User $sender, int $value, string $message)
    {
        parent::__construct($receiver, $receiver, $sender, time(), $value . " " . $message);
    }
}
