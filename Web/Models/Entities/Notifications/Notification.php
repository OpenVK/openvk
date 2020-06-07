<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User};
use openvk\Web\Util\DateTime;

class Notification
{
    private $recipient;
    private $originModel;
    private $targetModel;
    private $time;
    private $data;
    
    protected $actionCode = NULL;
    
    function __construct(User $recipient, $originModel, $targetModel, ?int $time = NULL, string $data = "")
    {
        $this->recipient   = $recipient;
        $this->originModel = $originModel;
        $this->targetModel = $targetModel;
        $this->time        = $time ?? time();
        $this->data        = $data;
    }
    
    private function encodeType(object $model): int
    {
        return (int) json_decode(file_get_contents(__DIR__ . "/../../../../data/modelCodes.json"), true)[get_class($model)];
    }
    
    function getActionCode(): int
    {
        return $this->actionCode;
    }
    
    function setActionCode(int $code): void
    {
        $this->actionCode = $this->actionCode ?? $code;
    }
    
    function getTemplatePath(): string
    {
        return implode("_", [
            "./../components/notifications/$this->actionCode/",
            $this->encodeType($this->originModel),
            $this->encodeType($this->targetModel),
            ".xml"
        ]);
    }
    
    function getRecipient(): User
    {
        return $this->recipient;
    }
    
    function getModel(int $index): RowModel
    {
        switch($index) {
            case 0:
                return $this->originModel;
            case 1:
                return $this->targetModel;
        }
    }
    
    function getData(): string
    {
        return $this->data;
    }
    
    function getDateTime(): DateTime
    {
        return new DateTime($this->time);
    }
    
    function emit(): bool
    {
        if(!($e = eventdb()))
            return false;
        
        $edb = $e->getConnection();
        $edb->query("INSERT INTO notifications VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?)", ...[
            $this->recipient->getId(),
            $this->encodeType($this->originModel),
            $this->originModel->getId(),
            $this->encodeType($this->targetModel),
            $this->targetModel->getId(),
            $this->actionCode,
            $this->data,
            $this->time,
        ]);
        
        return true;
    }
}
