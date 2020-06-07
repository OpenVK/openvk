<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\Entities\{User, Post, Comment};

final class CommentNotification extends Notification
{
    protected $actionCode = 2;
    
    function __construct(User $recipient, Comment $comment, $postable, User $commenter)
    {
        parent::__construct($recipient, $postable, $commenter, time(), ovk_proc_strtr($comment->getText(), 10));
    }
}
