<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;

/**
 * Message entity.
 */
class Message extends RowModel
{
    protected $tableName = "messages";
    
    /**
     * Get origin of the message.
     * 
     * Returns either user or club.
     * 
     * @returns User|Club
     */
    function getSender(): ?RowModel
    {
        if($this->getRecord()->sender_type === 'openvk\Web\Models\Entities\User')
            return (new Users)->get($this->getRecord()->sender_id);
        else if($this->getRecord()->sender_type === 'openvk\Web\Models\Entities\Club')
            return (new Clubs)->get($this->getRecord()->sender_id);
    }
    
    /**
     * Get the destination of the message.
     * 
     * Returns either user or club.
     * 
     * @returns User|Club
     */
    function getRecipient(): ?RowModel
    {
        if($this->getRecord()->recipient_type === 'openvk\Web\Models\Entities\User')
            return (new Users)->get($this->getRecord()->recipient_id);
        else if($this->getRecord()->recipient_type === 'openvk\Web\Models\Entities\Club')
            return (new Clubs)->get($this->getRecord()->recipient_id);
    }
    
    /**
     * Get date of initial publication.
     * 
     * @returns DateTime
     */
    function getSendTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
    
    /**
     * Get date of last edit, if any edits were made, otherwise null.
     * 
     * @returns DateTime|null
     */
    function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        if(is_null($edited)) return NULL;
        
        return new DateTime($edited);
    }
    
    /**
     * Is this message an ad?
     * 
     * Messages can never be ads.
     * 
     * @returns false
     */
    function isAd(): bool
    {
        return false;
    }
    
    /**
     * Simplify to array
     * 
     * @returns array
     */
    function simplify(): array
    {
        $author = $this->getSender();
        
        return [
            "uuid"   => $this->getId(),
            "sender" => [
                "id"     => $author->getId(),
                "link"   => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $author->getURL(),
                "avatar" => $author->getAvatarUrl(),
                "name"   => $author->getFullName(),
            ],
            "timing" => [
                "sent"   => (string) $this->getSendTime()->format("%e %B %G" . tr("time_at_sp") . "%X"),
                "edited" => is_null($this->getEditTime()) ? null : (string) $this->getEditTime(),
            ],
            "text" => $this->getText(),
        ];
    }
    
    use Traits\TRichText;
}
