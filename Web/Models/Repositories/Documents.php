<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Document;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Util\EntityStream;

class Documents
{
    private $context;
    private $documents;

    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->documents = $this->context->table("documents");
    }

    private function toDocument(?ActiveRow $ar): ?Document
    {
        return is_null($ar) ? null : new Document($ar);
    }

    public function get(int $id): ?Document
    {
        return $this->toDocument($this->documents->get($id));
    }

    # By "Virtual ID" and "Absolute ID" (to not leak owner's id).
    public function getDocumentById(int $virtual_id, int $real_id, string $access_key = null): ?Document
    {
        $doc = $this->documents->where(['virtual_id' => $virtual_id, 'id' => $real_id]);
        /*if($access_key) {
            $doc->where("access_key", $access_key);
        }*/

        $doc = $doc->fetch();
        if (is_null($doc)) {
            return null;
        }

        $n_doc = new Document($doc);
        if (!$n_doc->checkAccessKey($access_key)) {
            return null;
        }

        return $n_doc;
    }

    public function getDocumentByIdUnsafe(int $virtual_id, int $real_id): ?Document
    {
        $doc = $this->documents->where(['virtual_id' => $virtual_id, 'id' => $real_id]);

        $doc = $doc->fetch();
        if (is_null($doc)) {
            return null;
        }

        $n_doc = new Document($doc);

        return $n_doc;
    }

    public function getDocumentsByOwner(int $owner, int $order = 0, int $type = -1): EntityStream
    {
        $search = $this->documents->where([
            "owner"    => $owner,
            "unlisted" => 0,
            "deleted"  => 0,
        ]);

        if (in_array($type, [1,2,3,4,5,6,7,8])) {
            $search->where("type", $type);
        }

        switch ($order) {
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

    public function getTypes(int $owner_id): array
    {
        $result = DatabaseConnection::i()->getConnection()->query("SELECT `type`, COUNT(*) AS `count` FROM `documents` WHERE `owner` = ? AND `deleted` = 0 AND `unlisted` = 0 GROUP BY `type` ORDER BY `type`", $owner_id);
        $response = [];
        foreach ($result as $res) {
            if ($res->count < 1 || $res->type == 0) {
                continue;
            }

            $name = tr("document_type_" . $res->type);
            $response[] = [
                "count" => $res->count,
                "type"  => $res->type,
                "name"  => $name,
            ];
        }

        return $response;
    }

    public function getTags(int $owner_id, ?int $type = 0): array
    {
        $query = "SELECT `tags` FROM `documents` WHERE `owner` = ? AND `deleted` = 0 AND `unlisted` = 0 ";
        if ($type > 0 && $type < 9) {
            $query .= "AND `type` = $type";
        }

        $query .= " AND `tags` IS NOT NULL ORDER BY `id`";
        $result = DatabaseConnection::i()->getConnection()->query($query, $owner_id);
        $tags = [];
        foreach ($result as $res) {
            $tags[] = $res->tags;
        }
        $imploded_tags = implode(",", $tags);
        $exploded_tags = array_values(array_unique(explode(",", $imploded_tags)));
        if ($exploded_tags[0] == "") {
            return [];
        }

        return array_slice($exploded_tags, 0, 50);
    }

    public function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false]): Util\EntityStream
    {
        $result = $this->documents->where("name LIKE ?", "%$query%")->where([
            "deleted" => 0,
            "folder_id != " => 0,
        ]);
        $order_str = 'id';

        switch ($order['type']) {
            case 'id':
                $order_str = 'created ' . ($order['invert'] ? 'ASC' : 'DESC');
                break;
        }

        foreach ($params as $paramName => $paramValue) {
            switch ($paramName) {
                case "type":
                    if ($paramValue < 1 || $paramValue > 8) {
                        break;
                    }
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

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Document", $result);
    }
}
