<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\{Post, User, PostChangeRecord};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class PostsChanges
{
    private $context;
    private $changes;

    function __construct()
    {
        $this->context   = DatabaseConnection::i()->getContext();
        $this->changes   = $this->context->table("posts_changes");
    }

    function toChangeRecord(?ActiveRow $ar): ?PostChangeRecord
    {
        return is_null($ar) ? NULL : new PostChangeRecord($ar);
    }

    function get(int $id): ?PostChangeRecord
    {
        return $this->toChangeRecord($this->changes->get($id));
    }

    function getListByWid(int $wid): \Traversable
    {
        foreach ($this->changes->where("wall_id", $wid)->fetch() as $record)
            yield new PostChangeRecord($record);
    }

    function getAllHistoryById(int $wid, int $vid): \Traversable
    {
        foreach($this->changes->where(["wall_id" => $wid, "virtual_id" => $vid]) as $record)
            yield new PostChangeRecord($record);
    }

    function getHistoryById(int $wid, int $vid, int $page = 1): \Traversable
    {
        foreach($this->changes->where(["wall_id" => $wid, "virtual_id" => $vid])->page($page, 5) as $record)
            yield new PostChangeRecord($record);
    }
}
