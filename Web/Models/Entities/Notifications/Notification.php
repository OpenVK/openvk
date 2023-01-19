<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\Notifications;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User};
use openvk\Web\Util\DateTime;
use RdKafka\{Conf, Producer};

class Notification
{
    private $recipient;
    private $originModel;
    private $targetModel;
    private $time;
    private $data;
    
    protected $actionCode = NULL;
    protected $threshold  = -1;
    
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
    
    function reverseModelOrder(): bool
    {
        return false;
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
        
        $data = [
            "recipient"         => $this->recipient->getId(),
            "originModelType"   => $this->encodeType($this->originModel),
            "originModelId"     => $this->originModel->getId(),
            "targetModelType"   => $this->encodeType($this->targetModel),
            "targetModelId"     => $this->targetModel->getId(),
            "actionCode"        => $this->actionCode,
            "additionalPayload" => $this->data,
            "timestamp"         => $this->time,
        ];
        
        $edb = $e->getConnection();
        if($this->threshold !== -1) {
            # Event is thersholded, check if there is similar event
            $query = <<<'QUERY'
                SELECT * FROM `notifications` WHERE `recipientType`=0 AND `recipientId`=? AND `originModelType`=? AND `originModelId`=? AND `targetModelType`=? AND `targetModelId`=? AND `modelAction`=? AND `additionalData`=? AND `timestamp` > (? - ?)
QUERY;
            $result = $edb->query($query, ...array_merge(array_values($data), [ $this->threshold ]));
            if($result->getRowCount() > 0)
                return false;
        }
         
        $edb->query("INSERT INTO notifications VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?)", ...array_values($data));
        
        $kafkaConf = OPENVK_ROOT_CONF["openvk"]["credentials"]["notificationsBroker"];
        if($kafkaConf["enable"]) {
            $kafkaConf  = $kafkaConf["kafka"];
            $brokerConf = new Conf();
            $brokerConf->set("log_level", (string) LOG_DEBUG);
            $brokerConf->set("debug", "all");
            
            $producer = new Producer($brokerConf);
            $producer->addBrokers($kafkaConf["addr"] . ":" . $kafkaConf["port"]);
            
            $descriptor = implode(",", [
                str_replace("\\", ".", get_class($this)),
                $this->recipient->getId(),
                base64_encode(serialize((object) $data)),
            ]);
            
            $notifTopic = $producer->newTopic($kafkaConf["topic"]);
            $notifTopic->produce(RD_KAFKA_PARTITION_UA, RD_KAFKA_MSG_F_BLOCK, $descriptor);
            $producer->flush(100);
        }
        
        return true;
    }
}
