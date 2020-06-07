<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Post};

final class WallPostNotification extends Notification
{
    protected $actionCode = 3;
    
    function __construct(User $recipient, Post $post, User $poster)
    {
        parent::__construct($recipient, $post, $poster, time(), ovk_proc_strtr($post->getText(), 10));
    }
}
