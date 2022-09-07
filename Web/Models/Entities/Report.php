<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\{Users, Posts, Photos, Videos, Clubs};
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Database\Table\Selection;

class Report extends RowModel
{
    protected $tableName = "reports";

    function getId(): int
    {
        return $this->getRecord()->id;
    }
    
    function getStatus(): int
    {
        return $this->getRecord()->status;
    }
    
    function getContentType(): string
    {
        return $this->getRecord()->type;
    }
    
    function getReason(): string
    {
        return $this->getRecord()->reason;
    }
    
    function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->date);
    }
    
    function isDeleted(): bool
    {
        if ($this->getRecord()->deleted === 0) 
        {
            return false;
        } elseif ($this->getRecord()->deleted === 1) {
            return true;
        }
    }
    
    function authorId(): int
    {
        return $this->getRecord()->user_id;
    }
    
    function getUser(): user
    {
        return (new Users)->get($this->getRecord()->user_id);
    }

    function getContentId(): int
    {
        return $this->getRecord()->target_id;
    }

    function getContentObject()
    {
        if ($this->getContentType() == "post") return (new Posts)->get($this->getContentId());
        else if ($this->getContentType() == "photo") return (new Photos)->get($this->getContentId());
        else if ($this->getContentType() == "video") return (new Videos)->get($this->getContentId());
        else if ($this->getContentType() == "group") return (new Clubs)->get($this->getContentId());
        else return null;
    }

    function getAuthor(): RowModel
    {
        return (new Posts)->get($this->getContentId())->getOwner();
    }

    // TODO: Localize that
    function banUser()
    {
        $this->getAuthor()->ban("Banned by report. Ask Technical support for ban reason");
    }

    function deleteContent()
    {
        $this->getAuthor()->adminNotify("Ваш контент, который вы опубликовали " . $this->getContentObject()->getPublicationTime() . " был удалён модераторами инстанса. За повторные или серьёзные нарушения вас могут заблокировать.");
        $this->getContentObject()->delete();
        $this->setDeleted(1);
        $this->save();
    }
}
