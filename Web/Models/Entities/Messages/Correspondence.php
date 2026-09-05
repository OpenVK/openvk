<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Messages;

use Chandler\Database\DatabaseConnection;
use Chandler\Security\Authenticator;
use openvk\Web\Util\IMBroker;
use openvk\Web\Models\Entities\Messages\Message;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;
use Nette\Database\Table\ActiveRow;

/**
 * A repository of messages sent between correspondents.
 *
 * Since the introduction of the IM microservice, this acts as a thin
 * client that forwards read/write operations to the microservice through
 * the IM broker, while keeping the same public API to preserve backwards
 * compatibility with the legacy callers.
 */
class Correspondence
{
    /**
     * @var RowModel[] Array of correspondents (usually two)
     */
    private $correspondents;
    /**
     * @var \Nette\Database\Table\Selection Messages table
     */
    private $messages;
    /**
     * @var IMBroker|null Cached broker instance
     */
    private $broker;

    public const CAP_BEHAVIOUR_END_MESSAGE_ID   = 1;
    public const CAP_BEHAVIOUR_START_MESSAGE_ID = 2;

    private const USER_CLASS = "openvk\\Web\\Models\\Entities\\User";
    private const CLUB_CLASS = "openvk\\Web\\Models\\Entities\\Club";

    /**
     * Correspondence constructor.
     *
     * Requires two users/clubs to construct.
     *
     * @param RowModel $correspondent        - first correspondent
     * @param RowModel $anotherCorrespondent - another correspondent
     */
    public function __construct(RowModel $correspondent, RowModel $anotherCorrespondent)
    {
        $this->correspondents = [$correspondent, $anotherCorrespondent];
        // $this->messages       = DatabaseConnection::i()->getContext()->table("messages");
        $this->broker         = IMBroker::i();
    }

    /**
     * Convert a correspondent entity into a VK-style peer id.
     *
     * Users carry a positive id, clubs a negative one.
     */
    private function peerIdOf(RowModel $correspondent): int
    {
        $id = $correspondent->getId();

        return get_class($correspondent) === self::CLUB_CLASS ? $id * -1 : $id;
    }

    /**
     * Get the peer id with which the given correspondent shares this
     * conversation (the "other side").
     */
    private function getPeerId(?int $forUser = null): int
    {
        if (!is_null($forUser)) {
            foreach ($this->correspondents as $correspondent) {
                if ($correspondent->getId() === $forUser) {
                    $index = $this->correspondents[0] === $correspondent ? 1 : 0;

                    return $this->peerIdOf($this->correspondents[$index]);
                }
            }
        }

        return $this->peerIdOf($this->correspondents[1]);
    }

    /**
     * Resolve the actor id used for authorising broker requests.
     * Falls back to the first correspondent when no explicit actor is given.
     */
    private function getActorId(?int $senderId = null): int
    {
        return $senderId ?? $this->correspondents[0]->getId();
    }

    /**
     * Invoke a method on the IM microservice and return the decoded payload.
     *
     * @returns mixed|null payload, or null when the broker is unavailable/failed
     */
    private function invoke(int $senderId, string $method, array $params = [])
    {
        if (!$this->broker->isEnabled()) {
            return null;
        }

        $response = $this->broker->invokeMethod($senderId, $method, $params);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        return $data['response'] ?? $data;
    }

    /**
     * Turn a raw message array coming from the IM microservice into a legacy
     * Message entity, so old callers keep working.
     */
    private function hydrateMessage(array $item): ?Message
    {
        $fromId = (int) ($item['from_id'] ?? $item['sender_id'] ?? 0);
        $isClub = $fromId < 0;
        $abs    = abs($fromId);

        // Determine who the sender is among the two correspondents.
        $senderIdx = null;
        foreach ($this->correspondents as $idx => $correspondent) {
            $matchesClass = $isClub
                ? get_class($correspondent) === self::CLUB_CLASS
                : get_class($correspondent) === self::USER_CLASS;
            if ($matchesClass && (int) $correspondent->getId() === $abs) {
                $senderIdx = $idx;
                break;
            }
        }

        $sender    = $senderIdx !== null ? $this->correspondents[$senderIdx] : $this->correspondents[0];
        $recipient = $this->correspondents[$senderIdx === 0 ? 1 : 0];

        $id      = (int) ($item['id'] ?? 0);
        $created = (int) ($item['date'] ?? time());
        $edited  = $item['edited'] ?? null;

        $data = [
            "id"             => $id,
            "sender_id"      => $sender->getId(),
            "sender_type"    => get_class($sender),
            "recipient_id"   => $recipient->getId(),
            "recipient_type" => get_class($recipient),
            "content"        => (string) ($item['text'] ?? ""),
            "created"        => $created,
            "edited"         => $edited,
            "unread"         => isset($item['unread']) && $item['unread'] ? 1 : 0,
            "deleted"        => isset($item['deleted']) && $item['deleted'] ? (int) $item['deleted'] : 0,
        ];

        return new Message(new ActiveRow((array) $data, $this->messages));
    }

    /**
     * Get /im?sel url.
     *
     * @returns string - URL
     */
    public function getURL(): string
    {
        return "/im?sel=" . $this->getID();
    }

    public function getID(): int
    {
        return $this->peerIdOf($this->correspondents[1]);
    }

    /**
     * Get correspondents as array.
     *
     * @returns RowModel[] Array of correspondents (usually two)
     */
    public function getCorrespondents(): array
    {
        return $this->correspondents;
    }

    /**
     * Fetch messages from the IM microservice.
     *
     * @param int|null $capBehavior - how to cap the result (start/end message id)
     * @param int|null $cap         - capping message id
     * @param int|null $limit       - messages per page (defaults to default per page count)
     * @param int|null $padding     - offset
     * @param bool     $reverse     - reverse chronological order (oldest first)
     * @returns Message[]
     */
    public function getMessages(int $capBehavior = 1, ?int $cap = null, ?int $limit = null, ?int $padding = null, bool $reverse = false): array
    {
        $actor = $this->getActorId();
        $limit = $limit ?? OPENVK_DEFAULT_PER_PAGE;

        $params = [
            "peer_id"          => (string) $this->getPeerId(),
            "count"            => (string) max(1, $limit),
            "offset"           => (string) max(0, $padding ?? 0),
            "start_message_id" => (string) ($cap ?? 0),
        ];

        // When capping by start message id, the microservice history query
        // is already anchors from that id; otherwise we simply start from
        // the newest messages.
        $payload = $this->invoke($actor, "messages.getHistory", $params);
        if (!is_array($payload) || empty($payload['items'])) {
            return [];
        }

        $msgs = [];
        foreach (array_values($payload['items']) as $item) {
            $itemId = (int) ($item['id'] ?? 0);

            // Apply capping locally so behaviour is deterministic regardless
            // of the microservice's native parameter support.
            if (!is_null($cap)) {
                if ($capBehavior === self::CAP_BEHAVIOUR_END_MESSAGE_ID && $itemId >= $cap) {
                    continue;
                }
                if ($capBehavior === self::CAP_BEHAVIOUR_START_MESSAGE_ID && $itemId <= $cap) {
                    continue;
                }
            }

            $hydrated = $this->hydrateMessage((array) $item);
            if ($hydrated !== null) {
                $msgs[] = $hydrated;
            }
        }

        if ($reverse) {
            $msgs = array_reverse($msgs);
        }

        return $msgs;
    }

    /**
     * Get last message from correspondence.
     *
     * @returns Message|null - message, if any
     */
    public function getPreviewMessage(): ?Message
    {
        $messages = $this->getMessages(1, null, 1, 0);

        return $messages[0] ?? null;
    }

    /**
     * Get last message from correspondence from user.
     *
     * @returns Message|null - message, if any
     */
    public function getLastReadedMessage(int $user_id): ?Message
    {
        return $this->getPreviewMessage();
    }

    /**
     * Send message through the IM microservice.
     *
     * @deprecated Use the IM API instead.
     * @returns Message|false - resulting message, or false in case of non-successful transaction
     */
    public function sendMessage(Message $message, bool $dontReverse = false)
    {
        $ids     = [$this->correspondents[0]->getId(), $this->correspondents[1]->getId()];
        $classes = [get_class($this->correspondents[0]), get_class($this->correspondents[1])];

        if (!$dontReverse) {
            $user = (new Users())->getByChandlerUser(Authenticator::i()->getUser());
            if (!$user) {
                return false;
            }

            if ($ids[1] === $user->getId()) {
                $ids     = array_reverse($ids);
                $classes = array_reverse($classes);
            }
        }

        // Keep the legacy entity in sync for the caller.
        /* $message->setSender_Id($ids[0]);
        $message->setRecipient_Id($ids[1]);
        $message->setSender_Type($classes[0]);
        $message->setRecipient_Type($classes[1]);
        $message->setCreated(time());
        $message->setUnread(1); */

        // Locate the actual sender/recipient entities among the two
        // correspondents so we can build a correct peer id.
        $senderEntity = null;
        $recipient    = null;
        foreach ($this->correspondents as $cand) {
            if ((int) $cand->getId() === (int) $ids[0] && get_class($cand) === $classes[0]) {
                $senderEntity = $cand;
            }
            if ((int) $cand->getId() === (int) $ids[1] && get_class($cand) === $classes[1]) {
                $recipient = $cand;
            }
        }
        $senderEntity = $senderEntity ?? $this->correspondents[0];
        $recipient    = $recipient ?? $this->correspondents[1];

        $params = [
            "peer_id"   => (string) $this->peerIdOf($recipient),
            "message"   => $message->getText(false),
            "random_id" => (string) rand(1, 2147483647),
        ];

        $sentId = $this->invoke($senderEntity->getId(), "messages.send", $params);
        if ($sentId === null) {
            return false;
        }

        if (is_array($sentId)) {
            $sentId = (int) ($sentId['message_id'] ?? $sentId['response'] ?? 0);
        }

        return $message;
    }

    /**
     * Send typing event through the IM microservice.
     *
     * @returns true|false
     */
    public function sendTypingEvent()
    {
        $actor = $this->getActorId();
        $peer  = $this->getPeerId($actor);

        if ($peer === 0) {
            return false;
        }

        $result = $this->invoke($actor, "messages.setActivity", [
            "peer_id" => (string) $peer,
            "type"    => "typing",
        ]);

        return $result !== null;
    }
}
