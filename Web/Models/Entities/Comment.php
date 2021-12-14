<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\RowModel;

class Comment extends Post
{
    protected $tableName = "comments";
    protected $upperNodeReferenceColumnName = "owner";
    
    function getPrettyId(): string
    {
        return $this->getRecord()->id;
    }
    
    function getVirtualId(): int
    {
        return 0;
    }
    
    function getTarget(): ?Postable
    {
        $entityClassName = $this->getRecord()->model;
        $repoClassName   = str_replace("Entities", "Repositories", $entityClassName) . "s";
        $entity          = (new $repoClassName)->get($this->getRecord()->target);
        
        return $entity;
    }

    /**
     * May return fake owner (group), if flags are [1, (*)]
     * 
     * @param bool $honourFlags - check flags
     */
    function getOwner(bool $honourFlags = true, bool $real = false): RowModel
    {
        if($honourFlags && $this->isPostedOnBehalfOfGroup() && $this->getTarget() instanceof Post)
            return (new Clubs)->get(abs($this->getTarget()->getTargetWall()));

        return parent::getOwner($honourFlags, $real);
    }

    function canBeDeletedBy(User $user): bool
    {
        return $this->getOwner()->getId() == $user->getId() ||
               $this->getTarget()->getOwner()->getId() == $user->getId() ||
               $this->getTarget() instanceof Post && $this->getTarget()->getTargetWall() < 0 && (new Clubs)->get(abs($this->getTarget()->getTargetWall()))->canBeModifiedBy($user);
    }
}
