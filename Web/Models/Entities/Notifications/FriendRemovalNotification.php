<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\User;

final class FriendRemovalNotification extends Notification
{
    protected $actionCode = 4;
    
    function __construct(User $recipient, User $friend, User $remover)
    {
        parent::__construct($recipient, $friend, $remover, time(), "");
    }
}
