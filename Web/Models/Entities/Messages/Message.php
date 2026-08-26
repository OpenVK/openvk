<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Messages;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Entities\Photo;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;
use openvk\Web\Util\IMBroker;

/**
 * Message entity.
 * @deprecated Use IM API instead.
 */
class Message
{
    private $data;

    public function __construct($data = null)
    {
        $this->data = $data ? $data : new \stdClass();
    }

    private function getRecord()
    {
        if (is_array($this->data)) {
            return (object) $this->data;
        }

        return $this->data;
    }

    /**
     * Get message ID.
     *
     * @returns int
     */
    public function getId(): ?int
    {
        return $this->getRecord()->id;
    }
    /**
     * Get message text.
     *
     * @returns string
     */
    public function getText(bool $richText = false): string
    {
        return $this->getRecord()->content ?? $this->getRecord()->text;
    }

    public function getAttachmentsString()
    {
        return $this->getRecord()->attachments;
    }

    public function getSenderId()
    {
        return $this->getRecord()->from_id;
    }

    public function getPeerId()
    {
        return $this->getRecord()->peer_id;
    }

    public function getOwner() 
    {
        return get_entity_by_id($this->getSenderId());
    }

    /**
     * Sets message text.
     *
     * @returns void
     */
    public function setContent(string $text): void
    {
        $this->data->content = $text;
    }
    /**
     * Get origin of the message.
     *
     * Returns either user or club.
     *
     * @returns User|Club
     */
    public function getSender(): ?RowModel
    {
        if ($this->getRecord()->sender_type === 'openvk\Web\Models\Entities\User') {
            return (new Users())->get($this->getRecord()->sender_id);
        } elseif ($this->getRecord()->sender_type === 'openvk\Web\Models\Entities\Club') {
            return (new Clubs())->get($this->getRecord()->sender_id);
        } else {
            return null;
        }
    }

    /**
     * Get the destination of the message.
     *
     * Returns either user or club.
     *
     * @returns User|Club
     */
    public function getRecipient(): ?RowModel
    {
        if ($this->getRecord()->recipient_type === 'openvk\Web\Models\Entities\User') {
            return (new Users())->get($this->getRecord()->recipient_id);
        } elseif ($this->getRecord()->recipient_type === 'openvk\Web\Models\Entities\Club') {
            return (new Clubs())->get($this->getRecord()->recipient_id);
        } else {
            return null;
        }
    }

    public function getUnreadState(): int
    {
        trigger_error("TODO: use isUnread", E_USER_DEPRECATED);

        return (int) $this->isUnread();
    }

    /**
     * Get date of initial publication.
     *
     * @returns DateTime
     */
    public function getSendTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }

    public function getSendTimeHumanized(): string
    {
        $dateTime = new DateTime($this->getRecord()->created);

        if ($dateTime->format("%d.%m.%y") == ovk_strftime_safe("%d.%m.%y", time())) {
            return $dateTime->format("%T");
        } else {
            return $dateTime->format("%d.%m.%y");
        }
    }

    /**
     * Get date of last edit, if any edits were made, otherwise null.
     *
     * @returns DateTime|null
     */
    public function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        if (is_null($edited)) {
            return null;
        }

        return new DateTime($edited);
    }

    /**
     * Is this message an ad?
     *
     * Messages can never be ads.
     *
     * @returns false
     */
    public function isAd(): bool
    {
        return false;
    }

    public function isUnread(): bool
    {
        return (bool) $this->getRecord()->unread;
    }

    public static function fromGlobalId(int $global_id, int $from_id = 0): ?Message
    {
        $broker = IMBroker::i();
        $response = $broker->invokeMethod($from_id, "messages.getById", [
            "message_ids" => $global_id,
        ]);

        if ($response == false) {
            return null;
        }

        $data = json_decode($response, true);
        if ($data == null || $data["response"] == null) {
            return null;
        }

        return new Message($data["response"]["items"][0]);
    }

    /**
     * Simplify to array
     *
     * @returns array
     */
    public function simplify(): array
    {
        $author = $this->getSender();

        $attachments = [];
        foreach ($this->getChildren() as $attachment) {
            if ($attachment instanceof Photo) {
                $attachments[] = [
                    "type"  => "photo",
                    "link"  => "/photo" . $attachment->getPrettyId(),
                    "photo" => [
                        "url"     => $attachment->getURL(),
                        "caption" => $attachment->getDescription(),
                    ],
                ];
            } else {
                $attachments[] = [
                    "type"  => "unknown",
                ];

                # throw new \Exception("Unknown attachment type: " . get_class($attachment));
            }
        }

        return [
            "uuid"   => $this->getId(),
            "sender" => [
                "id"     => $author->getId(),
                "link"   => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $author->getURL(),
                "avatar" => $author->getAvatarUrl(),
                "name"   => $author->getFirstName(),
            ],
            "timing" => [
                "sent"   => (string) $this->getSendTimeHumanized(),
                "edited" => is_null($this->getEditTime()) ? null : (string) $this->getEditTime(),
            ],
            "text"        => $this->getText(),
            "read"        => !$this->isUnread(),
            "attachments" => $attachments,
        ];
    }
}
