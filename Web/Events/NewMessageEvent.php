<?php declare(strict_types=1);
namespace openvk\Web\Events;
use openvk\Web\Models\Entities\Message;
use openvk\Web\Models\Repositories\Messages;

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
    
    function getVKAPISummary(int $userId): array
    {
        $msg  = (new Messages)->get($this->payload["uuid"]);
        $peer = $msg->getSender()->getId();
        if($peer === $userId)
            $peer = $msg->getRecipient()->getId();
        
        return [
            4,                                # event type
            256,                              # checked for spam flag
            $peer,                            # TODO calculate peer correctly
            $msg->getSendTime()->timestamp(), # creation time in unix
            $msg->getText(),                  # text (formatted)
            [],                               # empty attachments
            $msg->getId() << 2,               # id as random_id
        ];
    }
}
