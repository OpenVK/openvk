<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;

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
}
