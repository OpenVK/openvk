<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Traits;
use openvk\Web\Models\Entities\Attachable;
use Chandler\Database\DatabaseConnection;

trait TAttachmentHost
{
    private function composeAttachmentRequestData(Attachable $attachment): array
    {
        return [
            "target_type"     => get_class($this),
            "target_id"       => $this->getId(),
            "attachable_type" => get_class($attachment),
            "attachable_id"   => $attachment->getId(),
        ];
    }
    
    function getChildren(): \Traversable
    {
        $sel = DatabaseConnection::i()->getContext()
                                      ->table("attachments")
                                      ->where("target_id", $this->getId())
                                      ->where("attachments.target_type", get_class($this));
        foreach($sel as $rel) {
            $repoName = $rel->attachable_type . "s";
            $repoName = str_replace("Entities", "Repositories", $repoName);
            $repo     = new $repoName;
            
            yield $repo->get($rel->attachable_id);
        }
    }
    
    function attach(Attachable $attachment): void
    {
        DatabaseConnection::i()->getContext()
                               ->table("attachments")
                               ->insert($this->composeAttachmentRequestData($attachment));
    }
    
    function detach(Attachable $attachment): bool
    {
        $res = DatabaseConnection::i()->getContext()
                               ->table("attachments")
                               ->where($this->composeAttachmentRequestData($attachment))
                               ->delete();
        
        return $res > 0;
    }
    
    function unwire(): void
    {
        $this->getRecord()
                    ->related("attachments.target_id")
                    ->where("attachments.target_type", get_class($this))
                    ->delete();
    }
}
