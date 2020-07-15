<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Post};

final class LikeNotification extends Notification
{
    protected $actionCode = 0;
    protected $threshold  = 120;
    
    function __construct(User $recipient, Post $post, User $liker)
    {
        parent::__construct($recipient, $post, $liker, time(), "");
    }
}
