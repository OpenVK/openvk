<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\Club;
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

    public function getContentObject()
    {
        if ($this->getContentType() == "post") {
            return (new Posts())->get($this->getContentId());
        } elseif ($this->getContentType() == "photo") {
            return (new Photos())->get($this->getContentId());
        } elseif ($this->getContentType() == "video") {
            return (new Videos())->get($this->getContentId());
        } elseif ($this->getContentType() == "group") {
            return (new Clubs())->get($this->getContentId());
        } elseif ($this->getContentType() == "comment") {
            return (new Comments())->get($this->getContentId());
        } elseif ($this->getContentType() == "note") {
            return (new Notes())->get($this->getContentId());
        } elseif ($this->getContentType() == "app") {
            return (new Applications())->get($this->getContentId());
        } elseif ($this->getContentType() == "user") {
            return (new Users())->get($this->getContentId());
        } elseif ($this->getContentType() == "audio") {
            return (new Audios())->get($this->getContentId());
        } elseif ($this->getContentType() == "doc") {
            return (new Documents())->get($this->getContentId());
        } else {
            return null;
        }
    }

    public function getAuthor(): RowModel
    {
        return $this->getContentObject()->getOwner();
    }

    public function getReportAuthor(): User
    {
        return (new Users())->get($this->getRecord()->user_id);
    }

    public function banUser($initiator)
    {
        $reason = $this->getContentType() !== "user" ? ("**content-" . $this->getContentType() . "-" . $this->getContentId() . "**") : ("Подозрительная активность");
        $this->getAuthor()->ban($reason, false, time() + $this->getAuthor()->getNewBanTime(), $initiator);
    }

    public function deleteContent()
    {
        if ($this->getContentType() !== "user") {
            $pubTime = $this->getContentObject()->getPublicationTime();
            if (method_exists($this->getContentObject(), "getName")) {
                $name = $this->getContentObject()->getName();
                $placeholder = "$pubTime ($name)";
            } else {
                $placeholder = "$pubTime";
            }

            if ($this->getAuthor() instanceof Club) {
                $name = $this->getAuthor()->getName();
                $this->getAuthor()->getOwner()->adminNotify("Ваш контент, который опубликовали $placeholder в созданной вами группе \"$name\" был удалён модераторами инстанса. За повторные или серьёзные нарушения группу могут заблокировать.");
            } else {
                $this->getAuthor()->adminNotify("Ваш контент, который вы опубликовали $placeholder был удалён модераторами инстанса. За повторные или серьёзные нарушения вас могут заблокировать.");
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
