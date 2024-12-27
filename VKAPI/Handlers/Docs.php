<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Document;

final class Docs extends VKAPIRequestHandler
{
    function add(int $owner_id, int $doc_id, ?string $access_key): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function delete(int $owner_id, int $doc_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function restore(int $owner_id, int $doc_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function edit(int $owner_id, int $doc_id, ?string $title, ?string $tags, ?int $folder_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function get(int $count = 30, int $offset = 0, int $type = 0, int $owner_id = NULL, int $return_tags = 0): int
    {
        $this->requireUser();

        return 0;
    }

    function getById(string $docs, int $return_tags = 0): int
    {
        $this->requireUser();

        return 0;
    }

    function getTypes(?int $owner_id)
    {
        $this->requireUser();

        return [];
    }

    function getUploadServer(?int $group_id = NULL)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }
    
    function getWallUploadServer(?int $group_id = NULL)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function save(string $file, string $title, string $tags, ?int $return_tags = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        return 0;
    }

    function search(string $q, int $search_own = 0, int $count = 30, int $offset = 0, int $return_tags = 0, int $type = 0, ?string $tags = NULL): object
    {
        $this->requireUser();

        return 0;
    }
}
