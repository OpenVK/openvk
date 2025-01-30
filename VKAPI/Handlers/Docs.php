<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Document;
use openvk\Web\Models\Repositories\Documents;

final class Docs extends VKAPIRequestHandler
{
    public function add(int $owner_id, int $doc_id, ?string $access_key): string
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $doc = (new Documents())->getDocumentById($owner_id, $doc_id, $access_key);
        if (!$doc || $doc->isDeleted()) {
            $this->fail(1150, "Invalid document id");
        }

        if (!$doc->checkAccessKey($access_key)) {
            $this->fail(15, "Access denied");
        }

        if ($doc->isCopiedBy($this->getUser())) {
            $this->fail(100, "One of the parameters specified was missing or invalid: this document already added");
        }

        $new_doc = $doc->copy($this->getUser());

        return $new_doc->getPrettyId();
    }

    public function delete(int $owner_id, int $doc_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $doc = (new Documents())->getDocumentByIdUnsafe($owner_id, $doc_id);
        if (!$doc || $doc->isDeleted()) {
            $this->fail(1150, "Invalid document id");
        }

        if (!$doc->canBeModifiedBy($this->getUser())) {
            $this->fail(1153, "Access to document is denied");
        }

        $doc->delete();

        return 1;
    }

    public function restore(int $owner_id, int $doc_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return $this->add($owner_id, $doc_id, "");
    }

    public function edit(int $owner_id, int $doc_id, ?string $title = "", ?string $tags = "", ?int $folder_id = 0, int $owner_hidden = -1): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $doc = (new Documents())->getDocumentByIdUnsafe($owner_id, $doc_id);
        if (!$doc || $doc->isDeleted()) {
            $this->fail(1150, "Invalid document id");
        }
        if (!$doc->canBeModifiedBy($this->getUser())) {
            $this->fail(1153, "Access to document is denied");
        }
        if (iconv_strlen($title ?? "") > 128 || iconv_strlen($title ?? "") < 0) {
            $this->fail(1152, "Invalid document title");
        }
        if (iconv_strlen($tags ?? "") > 256) {
            $this->fail(1154, "Invalid tags");
        }

        if ($title) {
            $doc->setName($title);
        }

        $doc->setTags($tags);
        if (in_array($folder_id, [0, 3])) {
            $doc->setFolder_id($folder_id);
        }
        if (in_array($owner_hidden, [0, 1])) {
            $doc->setOwner_hidden($owner_hidden);
        }

        try {
            $doc->setEdited(time());
            $doc->save();
        } catch (\Throwable $e) {
            return 0;
        }

        return 1;
    }

    public function get(int $count = 30, int $offset = 0, int $type = -1, int $owner_id = null, int $return_tags = 0, int $order = 0): object
    {
        $this->requireUser();
        if (!$owner_id) {
            $owner_id = $this->getUser()->getId();
        }

        if ($owner_id > 0 && $owner_id != $this->getUser()->getId()) {
            $this->fail(15, "Access denied");
        }

        $documents = (new Documents())->getDocumentsByOwner($owner_id, $order, $type);
        $res = (object) [
            "count" => $documents->size(),
            "items" => [],
        ];

        foreach ($documents->offsetLimit($offset, $count) as $doc) {
            $res->items[] = $doc->toVkApiStruct($this->getUser(), $return_tags == 1);
        }

        return $res;
    }

    public function getById(string $docs, int $return_tags = 0): array
    {
        $this->requireUser();

        $item_ids = explode(",", $docs);
        $response = [];
        if (sizeof($item_ids) < 1) {
            $this->fail(100, "One of the parameters specified was missing or invalid: docs is undefined");
        }

        foreach ($item_ids as $id) {
            $splitted_id = explode("_", $id);
            $doc = (new Documents())->getDocumentById((int) $splitted_id[0], (int) $splitted_id[1], $splitted_id[2]);
            if (!$doc || $doc->isDeleted()) {
                continue;
            }

            $response[] = $doc->toVkApiStruct($this->getUser(), $return_tags === 1);
        }

        return $response;
    }

    public function getTypes(?int $owner_id)
    {
        $this->requireUser();
        if (!$owner_id) {
            $owner_id = $this->getUser()->getId();
        }

        if ($owner_id > 0 && $owner_id != $this->getUser()->getId()) {
            $this->fail(15, "Access denied");
        }

        $types = (new Documents())->getTypes($owner_id);
        return [
            "count" => sizeof($types),
            "items" => $types,
        ];
    }

    public function getTags(?int $owner_id, ?int $type = 0)
    {
        $this->requireUser();
        if (!$owner_id) {
            $owner_id = $this->getUser()->getId();
        }

        if ($owner_id > 0 && $owner_id != $this->getUser()->getId()) {
            $this->fail(15, "Access denied");
        }

        $tags = (new Documents())->getTags($owner_id, $type);
        return $tags;
    }

    public function search(string $q = "", int $search_own = -1, int $order = -1, int $count = 30, int $offset = 0, int $return_tags = 0, int $type = 0, ?string $tags = null): object
    {
        $this->requireUser();

        $params    = [];
        $o_order   = ["type" => "id", "invert" => false];

        if (iconv_strlen($q) > 512) {
            $this->fail(100, "One of the parameters specified was missing or invalid: q should be not more 512 letters length");
        }

        if (in_array($type, [1,2,3,4,5,6,7,8])) {
            $params["type"] = $type;
        }

        if (iconv_strlen($tags ?? "") < 512) {
            $params["tags"] = $tags;
        }

        if ($search_own === 1) {
            $params["from_me"] = $this->getUser()->getId();
        }

        $documents = (new Documents())->find($q, $params, $o_order);
        $res = (object) [
            "count" => $documents->size(),
            "items" => [],
        ];

        foreach ($documents->offsetLimit($offset, $count) as $doc) {
            $res->items[] = $doc->toVkApiStruct($this->getUser(), $return_tags == 1);
        }

        return $res;
    }

    public function getUploadServer(?int $group_id = null)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    public function getWallUploadServer(?int $group_id = null)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    public function save(string $file, string $title, string $tags, ?int $return_tags = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }
}
