<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Users;

class Ticket extends RowModel
{
    protected $tableName = "tickets";
    
    private $overrideContentColumn = "text";

    function getId(): int
    {
        return $this->getRecord()->id;
    }
    
    function getStatus(): string
    {
        return tr("support_status_" . $this->getRecord()->type);
    }
    
    function getType(): int
    {
        return $this->getRecord()->type;
    }
    
    function getName(): string
    {
        return ovk_proc_strtr($this->getRecord()->name, 100);
    }
    
    function getContext(): string
    {
        $text = $this->getRecord()->text;
        $text = $this->formatLinks($text);
        $text = $this->removeZalgo($text);
        $text = nl2br($text);
        return $text;
    }
    
    function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
    
    function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }
    
    function getUser(): user
    {
        return (new Users)->get($this->getRecord()->user_id);
    }

    function getUserId(): int
    {
        return $this->getRecord()->user_id;
    }

    function isAd(): bool /* Эх, костыли... */
    {
    	return false;
    }

    use Traits\TRichText;
}
