<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\Messages\Message;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\{Applications, Comments, Notes, Reports, Audios, Documents, Users, Posts, Photos, Videos, Clubs};
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Database\Table\Selection;

class Report extends RowModel
{
    protected $tableName = "reports";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getStatus(): int
    {
        return $this->getRecord()->status;
    }

    public function getContentType(): string
    {
        return $this->getRecord()->type;
    }

    public function getReason(): string
    {
        return $this->getRecord()->reason;
    }

    public function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->date);
    }

    public function isDeleted(): bool
    {
        return $this->getRecord()->deleted === 1;
    }

    public function authorId(): int
    {
        return $this->getRecord()->user_id;
    }

    public function getUser(): User
    {
        return (new Users())->get((int) $this->getRecord()->user_id);
    }

    public function getContentId(): int
    {
        return (int) $this->getRecord()->target_id;
    }

    public function getContentObject(bool $extended = false)
    {
        switch ($this->getContentType()) {
            case "post":
                return (new Posts())->get($this->getContentId());
                break;
            case "photo":
                return (new Photos())->get($this->getContentId());
                break;
            case "video":
                return (new Videos())->get($this->getContentId());
                break;
            case "group":
                return (new Clubs())->get($this->getContentId());
                break;
            case "comment":
                return (new Comments())->get($this->getContentId());
                break;
            case "note":
                return (new Notes())->get($this->getContentId());
                break;
            case "app":
                return (new Applications())->get($this->getContentId());
                break;
            case "user":
                return (new Users())->get($this->getContentId());
                break;
            case "audio":
                return (new Audios())->get($this->getContentId());
                break;
            case "doc":
                return (new Documents())->get($this->getContentId());
                break;
            case "message":
                if ($extended == true) {
                    try {
                        #return Message::fromGlobalId($this->getContentId(), 0);
                        return Message::fromGlobalId($this->getContentId());
                    } catch (\Throwable $e) {
                        bdump($e);
                        return null;
                    }
                } else {
                    return new Message((object) [
                        "global_id" => $this->getContentId()
                    ]);
                }
                break;
        }

        return null;
    }

    public function getAuthor(): RowModel
    {
        return $this->getContentObject()->getOwner();
    }

    public function getReportAuthor(): User
    {
        return (new Users())->get($this->getRecord()->user_id);
    }

    public function banUser($initiator, string $reasonOwner = "")
    {
        $author = $this->getAuthor();
        $author->ban($reasonOwner, false, time() + $author->getNewBanTime(), $initiator);
    }

    public function deleteContent(string $reason = "")
    {
        if ($this->getContentType() === "message") {
            $obj = $this->getContentObject();
        }

        if (!in_array($this->getContentType(), ["message", "user"])) {
            $pubTime = $this->getContentObject()->getPublicationTime();
            $postId = method_exists($this->getContentObject(), "getPrettyId") ? $this->getContentObject()->getPrettyId() : $this->getContentObject()->getId();
            $reasonPlaceholder = "";
            if ($reason != "") {
                $reasonPlaceholder = " по причине " . $reason;
            }

            if ($this->getAuthor() instanceof Club) {
                $name = $this->getAuthor()->getName();
                $this->getAuthor()->getOwner()->adminNotify("Ваш контент с id $postId, который был опубликован $pubTime в созданной вами группе \"$name\" был удалён модераторами инстанса$reasonPlaceholder. За повторные или серьёзные нарушения группу могут заблокировать.");
            } else {
                $this->getAuthor()->adminNotify("Ваш контент с id $postId, который был опубликован $pubTime был удалён модераторами инстанса$reasonPlaceholder. За повторные или серьёзные нарушения вас могут заблокировать.");
            }
            $this->getContentObject()->delete($this->getContentType() !== "app");
        }

        $this->delete();
    }

    public function getDuplicates(): \Traversable
    {
        return (new Reports())->getDuplicates($this->getContentType(), $this->getContentId(), $this->getId());
    }

    public function getDuplicatesCount(): int
    {
        return count(iterator_to_array($this->getDuplicates()));
    }

    public function hasDuplicates(): bool
    {
        return $this->getDuplicatesCount() > 0;
    }

    public function getContentName(): string
    {
        $content_object = $this->getContentObject();
        if (!$content_object) {
            return 'unknown';
        }

        if (method_exists($content_object, "getCanonicalName")) {
            return $content_object->getCanonicalName();
        }

        return $this->getContentType() . " #" . $this->getContentId();
    }

    public function delete(bool $softly = true): void
    {
        if ($this->hasDuplicates()) {
            foreach ($this->getDuplicates() as $duplicate) {
                $duplicate->setDeleted(1);
                $duplicate->save();
            }
        }

        $this->setDeleted(1);
        $this->save();
    }
}
