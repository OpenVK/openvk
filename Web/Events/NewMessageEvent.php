<?php declare(strict_types=1);
namespace openvk\Web\Events;
use openvk\Web\Models\Entities\Message;

class NewMessageEvent implements ILPEmitable
{
    protected $payload;
    
    function __construct(Message $message)
    {
        $this->payload = $message->simplify();
    }
    
    function getLongPoolSummary(): object
    {
        return (object) [
            "type"    => "newMessage",
            "message" => $this->payload,
        ];
    }
}