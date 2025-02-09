<?php

declare(strict_types=1);

namespace openvk\VKAPI\Structures;

final class Conversation
{
    public $peer;
    public $last_message_id;
    public $in_read = 1;
    public $out_read = 1;
    public $is_marked_unread = false;
    public $important = true;
    public $can_write;
}
