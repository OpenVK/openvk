<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\{Postable, User, Comment};

final class ReplyCommentNotification extends Notification
{
    protected $actionCode = 8;

    public function __construct(User $recipient, Comment $comment, Postable $discussionHost, User $commenter)
    {
        parent::__construct($recipient, $comment, $commenter, time(), ovk_proc_strtr(strip_tags($comment->getText()), 400));
    }
}
