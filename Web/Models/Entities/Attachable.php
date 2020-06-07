<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\RowModel;

abstract class Attachable extends RowModel
{
    function getId(): int
    {
        return $this->getRecord()->id;
    }
    
    function getParents(): \Traversable
    {
        $sel = $this->getRecord()
                    ->related("attachments.attachable_id")
                    ->where("attachments.attachable_type", get_class($this));
        foreach($sel as $rel) {
            $repoName = $rel->target_type . "s";
            $repoName = str_replace("Entities", "Repositories", $repoName);
            $repo     = new $repoName;
            
            yield $repo->get($rel->target_id);
        }
    }
    
    /**
     * Deletes together with all references.
     */
    function delete(bool $softly = true): void
    {
        $this->getRecord()
             ->related("attachments.attachable_id")
             ->where("attachments.attachable_type", get_class($this))
             ->delete();
        
        parent::delete();
    }
}
