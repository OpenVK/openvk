<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Club};

final class ClubModeratorNotification extends Notification
{
    protected $actionCode = 5;
    
    function __construct(User $recipient, Club $group, User $admin)
    {
        parent::__construct($recipient, $group, $admin, time(), "");
    }
}
