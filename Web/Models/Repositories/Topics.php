<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Topic;
use openvk\Web\Models\Entities\Club;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Topics
{
    private $context;
    private $topics;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->topics  = $this->context->table("topics");
    }

    private function toTopic(?ActiveRow $ar): ?Topic
    {
        return is_null($ar) ? NULL : new Topic($ar);
    }
    
    function get(int $id): ?Topic
    {
        return $this->toTopic($this->topics->get($id));
    }

    function getTopicById(int $club, int $topic): ?Topic
    {
        return $this->toTopic($this->topics->where(["group" => $club, "virtual_id" => $topic, "deleted" => 0])->fetch());
    }
    
    function getClubTopics(Club $club, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;

        # Get pinned topics first
        $query  = "SELECT `id` FROM `topics` WHERE `pinned` = 1 AND `group` = ? AND `deleted` = 0 UNION SELECT `id` FROM `topics` WHERE `pinned` = 0 AND `group` = ? AND `deleted` = 0";
        $query .= " LIMIT " . $perPage . " OFFSET " . ($page - 1) * $perPage;

        foreach(DatabaseConnection::i()->getConnection()->query($query, $club->getId(), $club->getId()) as $topic) {
            $topic = $this->get($topic->id);
            if(!$topic) continue;
            
            yield $topic;
        }
    }
    
    function getClubTopicsCount(Club $club): int
    {
        return sizeof($this->topics->where([
            "group"   => $club->getId(),
            "deleted" => false
        ]));
    }

    function find(Club $club, string $query): \Traversable
    {
        return new Util\EntityStream("Topic", $this->topics->where("title LIKE ? AND group = ? AND deleted = 0", "%$query%", $club->getId()));
    }

    function getLastTopics(Club $club, ?int $count = NULL): \Traversable
    {
        $topics = $this->topics->where([
            "group"   => $club->getId(),
            "deleted" => false
        ])->page(1, $count ?? OPENVK_DEFAULT_PER_PAGE)->order("created DESC");
        
        foreach($topics as $topic)
            yield $this->toTopic($topic);
    }
}
