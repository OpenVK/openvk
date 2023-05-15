<?php declare(strict_types=1);
namespace openvk\VKAPI\Structures;

final class Note
{
    public $id;
    public $owner_id;
    public $title;
    public $text;
    public $date;
    public $comments;
    public $read_comments = 0;
    public $view_url;
    public $privacy_view;
    public $can_comment;
    public $text_wiki;
}
