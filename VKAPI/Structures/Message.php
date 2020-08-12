<?php declare(strict_types=1);
namespace openvk\VKAPI\Structures;

final class Message
{
    public $id;
    public $user_id;
    public $from_id;
    public $date;
    public $read_state;
    public $out;
    public $title = "";
    public $body;
    public $attachments = [];
    public $fwd_messages = [];
    public $emoji;
    public $important = 1;
    public $deleted = 0;
    public $random_id = NULL;
}
