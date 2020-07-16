<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Users;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Database\Table\Selection;

class TicketComment extends RowModel
{
    
    protected $tableName = "tickets_comments";

    function getId(): int
    {
        return $this->getRecord()->id;
    }
    function getUType(): int
    {
        return $this->getRecord()->user_type;
    }
    
    function getUser(): User 
    { 
        return (new Users)->get($this->getRecord()->user_id);
    } 
    
    function getAgentNumber(): ?string
    {
        if($this->getUType() === 0)
            return NULL;
        
        $salt = "kiraMiki";
        $hash = $this->getUser()->getId() . CHANDLER_ROOT_CONF["security"]["secret"] . $salt;
        $hash = hexdec(substr(hash("adler32", $hash), 0, 3));
        $hash = ceil(($hash * 999) / 4096); # proportionalize to 0-999
        
        return str_pad((string) $hash, 3, "0", STR_PAD_LEFT);
    }
    
    function getColorRotation(): ?int
    {
        if(is_null($agent = $this->getAgentNumber()))
            return NULL;
        
        $agent    = (int) $agent;
        $rotation = $agent > 500 ? ( ($agent * 360) / 999 ) : $agent; # cap at 360deg
        $values   = [0, 45, 160, 220, 310, 345]; # good looking colors
        usort($values, function($x, $y) use ($rotation) {
            # find closest
            return  abs($x - $rotation) - abs($y - $rotation);
        });
        
        return array_shift($values);
    }
    
    function getContext(): string
    {
        return $this->getRecord()->text;
    }
    
    function getTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
    
}
