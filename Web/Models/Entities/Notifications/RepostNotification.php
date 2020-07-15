<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Post};

final class RepostNotification extends Notification
{
    protected $actionCode = 1;
    protected $threshold  = 120;
    
    function __construct(User $recipient, Post $post, User $reposter)
    {
        parent::__construct($recipient, $post, $reposter, time(), "");
    }
}
