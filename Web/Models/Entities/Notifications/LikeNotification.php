<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\{User, Post};

final class LikeNotification extends Notification
{
    protected $actionCode = 0;
    protected $threshold  = 120;

    public function __construct(User $recipient, Post $post, User $liker, int $time)
    {
        parent::__construct($recipient, $post, $liker, $time, "");
    }

    public function toFeedbackStruct()
    {
        bdump($this);
        return (object) [
            "count" => 1,
            "items" => [(object) ["from_id" => $this->targetModel->getId()]],
        ];
    }
}
