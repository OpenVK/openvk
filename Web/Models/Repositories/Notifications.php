<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Notifications\Notification;
use openvk\Web\Models\Repositories\Users;

class Notifications
{
    private $edbc = NULL;
    private $modelCodes;
    
    function __construct()
    {
        $this->modelCodes = array_flip(json_decode(file_get_contents(__DIR__ . "/../../../data/modelCodes.json"), true));
    }
    
    private function getEDB(bool $throw = true): ?object
    {
        $eax = $this->edbc ?? eventdb();
        if(!$eax && $throw)
            throw new \RuntimeException("Event database err!");
        
        return is_null($eax) ? NULL : $eax->getConnection();
    }
    
    private function getModel(int $code, int $id): object
    {
        $repoClassName = str_replace("Entities", "Repositories", "\\" . $this->modelCodes[$code]) . "s";
        
        return (new $repoClassName)->get($id);
    }
    
    private function getQuery(User $user, bool $count = false, int $offset, bool $archived = false, int $page = 1, ?int $perPage = NULL): string
    {
        $query    = "SELECT " . ($count ? "COUNT(*) AS cnt" : "*") . " FROM notifications WHERE recipientType=0 ";
        $query   .= "AND timestamp " . ($archived ? "<" : ">") . "$offset AND recipientId=" . $user->getId();
        if(!$count) {
            $query .= " ORDER BY timestamp DESC";
            $query .= " LIMIT " . ($perPage ?? OPENVK_DEFAULT_PER_PAGE);
            $query .= " OFFSET " . ((($page - 1) * $perPage) ?? OPENVK_DEFAULT_PER_PAGE);
        }
        
        return $query;
    }
    
    function getNotificationCountByUser(User $user, int $offset, bool $archived = false): int
    {
        $db = $this->getEDB(false);
        if(!$db)
            return 0;
        
        $results = $db->query($this->getQuery($user, true, $offset, $archived));
        
        return $results->fetch()->cnt;
    }
    
    function getNotificationsByUser(User $user, int $offset, bool $archived = false, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $db = $this->getEDB(false);
        if(!$db) {
            yield from [];
            return;
        }
        
        $results  = $this->getEDB()->query($this->getQuery($user, false, $offset, $archived, $page, $perPage));
        foreach($results->fetchAll() as $notif) {
            $originModel = $this->getModel($notif->originModelType, $notif->originModelId);
            $targetModel = $this->getModel($notif->targetModelType, $notif->targetModelId);
            $recipient   = (new Users)->get($notif->recipientId);
            
            $notification = new Notification($recipient, $originModel, $targetModel, $notif->timestamp, $notif->additionalData);
            $notification->setActionCode($notif->modelAction);
            yield $notification;
        }
    }
}
