<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Document;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Util\EntityStream;

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
    
    function get(int $id): ?Document
    {
        return $this->toDocument($this->documents->get($id));
    }
    
    # By "Virtual ID" and "Absolute ID" (to not leak owner's id).
    function getDocumentById(int $virtual_id, int $real_id, ?string $access_key = NULL): ?Document
    {
        $doc = $this->documents->where(['virtual_id' => $virtual_id, 'id' => $real_id]);
        /*if($access_key) {
            $doc->where("access_key", $access_key);
        }*/

        $doc = $doc->fetch();
        if(is_null($doc))
            return NULL;

        $n_doc = new Document($doc);
        if(!$n_doc->checkAccessKey($access_key))
            return NULL;

        return $n_doc;
    }

    function getDocumentsByOwner(int $owner, int $order = 0, int $type = -1): EntityStream
    {
        $search = $this->documents->where([
            "owner"    => $owner,
            "unlisted" => 0,
            "deleted"  => 0,
        ]);

        if(in_array($type, [1,2,3,4,5,6,7,8])) {
            $search->where("type", $type);
        }

        switch($order) {
            case 0:
                $search->order("id DESC");
                break;
            case 1:
                $search->order("name DESC");
                break;
            case 2:
                $search->order("filesize DESC");
                break;
        }

        return new EntityStream("Document", $search);
    }

    function getTypes(int $owner_id): array
    {
        $result = DatabaseConnection::i()->getConnection()->query("SELECT `type`, COUNT(*) AS `count` FROM `documents` WHERE `owner` = $owner_id AND `deleted` = 0 AND `unlisted` = 0 GROUP BY `type` ORDER BY `type`");
        $response = [];
        foreach($result as $res) {
            if($res->count < 1 || $res->type == 0) continue;

            $name = tr("document_type_".$res->type);
            $response[] = [
                "count" => $res->count,
                "type"  => $res->type,
                "name"  => $name,
            ];
        }

        return $response;
    }

    function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $result = $this->documents->where("name LIKE ?", "%$query%")->where([
            "deleted" => 0,
            "folder_id != " => 0, 
        ]);
        $order_str = 'id';

        switch($order['type']) {
            case 'id':
                $order_str = 'created ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        foreach($params as $paramName => $paramValue) {
            switch($paramName) {
                case "type":
                    if($paramValue < 1 || $paramValue > 8) continue;
                    $result->where("type", $paramValue);
                    break;
                case "tags":
                    $result->where("tags LIKE ?", "%$paramValue%");
                    break;
                case "from_me":
                    $result->where("owner", $paramValue);
                    break;
            }
        }

        if($order_str)
            $result->order($order_str);

        return new Util\EntityStream("Document", $result);
    }
}
