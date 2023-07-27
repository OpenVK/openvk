<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\User;

final class MentionNotification extends Notification
{
    protected $actionCode = 4;
    
    function __construct(User $recipient, User $target, User $mentioner)
    {
        parent::__construct($recipient, $target, $mentioner, time(), "");
    }
}
