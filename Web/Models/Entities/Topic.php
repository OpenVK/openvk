<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Util\DateTime;

class Topic extends Postable
{
    protected $tableName = "topics";
    protected $upperNodeReferenceColumnName = "group";

    /**
     * May return fake owner (group), if flags are [1, (*)]
     * 
     * @param bool $honourFlags - check flags
     */
    function getOwner(bool $honourFlags = true, bool $real = false): RowModel
    {
        if($honourFlags && $this->isPostedOnBehalfOfGroup())
            return $this->getClub();
        
        return parent::getOwner($real);
    }

    function getClub(): Club
    {
        return (new Clubs)->get($this->getRecord()->group);
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function isClosed(): bool
    {
        return (bool) $this->getRecord()->closed;
    }

    function isPinned(): bool
    {
        return (bool) $this->getRecord()->pinned;
    }

    function getPrettyId(): string
    {
        return $this->getRecord()->group . "_" . $this->getVirtualId();
    }

    function isPostedOnBehalfOfGroup(): bool
    {
        return ($this->getRecord()->flags & 0b10000000) > 0;
    }

    function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    function canBeModifiedBy(User $user): bool
    {
        return $this->getOwner(false)->getId() === $user->getId() || $this->getClub()->canBeModifiedBy($user);
    }

    function getLastComment(): ?Comment
    {
        $array = iterator_to_array($this->getLastComments(1));
        return isset($array[0]) ? $array[0] : NULL;
    }

    function getUpdateTime(): DateTime
    {
        $lastComment = $this->getLastComment();
        if(!is_null($lastComment))
            return $lastComment->getPublicationTime();
        else
            return $this->getEditTime() ?? $this->getPublicationTime();
    }

    function deleteTopic(): void 
    {
        $this->setDeleted(1);
        $this->unwire();
        $this->save();
    }
}
