<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Document;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Documents
{
    private $context;
    private $documents;
    
    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->documents = $this->context->table("documents");
    }
        
    private function toDocument(?ActiveRow $ar): ?Document
    {
        return is_null($ar) ? NULL : new Document($ar);
    }
    
    function get(int $id): ?Comment
    {
        return $this->toDocument($this->documents->get($id));
    }
    
    # By "Virtual ID" and "Absolute ID" (to not leak owner's id).
    function getDocumentById(int $virtual_id, int $real_id, ?string $access_key = NULL): ?Post
    {
        $doc = $this->documents->where(['virtual_id' => $virtual_id, 'id' => $real_id]);

        if($access_key) {
            $doc->where("access_key", $access_key);
        }

        $doc = $doc->fetch();
        if(!is_null($doc))
            return new Document($doc);
        else
            return NULL;
        
    }

    function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $result = $this->documents->where("title LIKE ?", "%$query%")->where("deleted", 0);
        $order_str = 'id';

        switch($order['type']) {
            case 'id':
                $order_str = 'created ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        foreach($params as $paramName => $paramValue) {
            switch($paramName) {
                case "before":
                    $result->where("created < ?", $paramValue);
                    break;
            }
        }

        if($order_str)
            $result->order($order_str);

        return new Util\EntityStream("Document", $result);
    }
}
