<?php

declare(strict_types=1);

namespace openvk\Web\Events;

use openvk\Web\Models\Entities\Message;
use openvk\Web\Models\Repositories\Messages;

class TypingEvent implements ILPEmitable
{
    protected $payload;

    public function __construct(int $userId)
    {
        $this->payload = $userId;
    }

    public function getLongPoolSummary(): object
    {
        return (object) [
            "type"    => "typing",
            "message" => $this->payload,
        ];
    }

    public function getVKAPISummary(int $userId): array
    {
        /*
         * $userId is intentionally not used to not break code
         *
         * Source:
         * https://dev.vk.com/ru/api/user-long-poll/getting-started
         */

        return [
            61,                               # event type
            $this->payload,                   # userId
            1
        ];
    }
}
