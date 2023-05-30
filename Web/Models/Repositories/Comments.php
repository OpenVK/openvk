<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Postable;
use openvk\Web\Models\Entities\Comment;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Comments
{
    private $context;
    private $comments;
    
    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->comments = $this->context->table("comments");
    }
    
    private function toComment(?ActiveRow $ar): ?Comment
    {
        return is_null($ar) ? NULL : new Comment($ar);
    }
    
    function get(int $id): ?Comment
    {
        return $this->toComment($this->comments->get($id));
    }
    
    function getCommentsByTarget(Postable $target, int $page, ?int $perPage = NULL, ?string $sort = "ASC"): \Traversable
    {
        $comments = $this->comments->where([
            "model"   => get_class($target),
            "target"  => $target->getId(),
            "deleted" => false,
        ])->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE)->order("created ".$sort);;
        
        foreach($comments as $comment)
            yield $this->toComment($comment);
    }

    function getLastCommentsByTarget(Postable $target, ?int $count = NULL): \Traversable
    {
        $comments = $this->comments->where([
            "model"   => get_class($target),
            "target"  => $target->getId(),
            "deleted" => false,
        ])->page(1, $count ?? OPENVK_DEFAULT_PER_PAGE)->order("created DESC");
        
        $comments = array_reverse(iterator_to_array($comments));
        foreach($comments as $comment)
            yield $this->toComment($comment);
    }
    
    function getCommentsCountByTarget(Postable $target): int
    {
        return sizeof($this->comments->where([
            "model"   => get_class($target),
            "target"  => $target->getId(),
            "deleted" => false,
        ]));
    }

    function find(string $query = "", array $pars = [], string $sort = "id"): Util\EntityStream
    {
        $query  = "%$query%";

        $notNullParams = [];

        foreach($pars as $paramName => $paramValue)
            if($paramName != "before" && $paramName != "after")
                $paramValue != NULL ? $notNullParams+=["$paramName" => "%$paramValue%"]   : NULL;
            else
                $paramValue != NULL ? $notNullParams+=["$paramName" => "$paramValue"]     : NULL;

        $result = $this->comments->where("content LIKE ?", $query)->where("deleted", 0);
        $nnparamsCount = sizeof($notNullParams);

        if($nnparamsCount > 0) {
            foreach($notNullParams as $paramName => $paramValue) {
                switch($paramName) {
                    case "before":
                        $result->where("created < ?", $paramValue);
                        break;
                    case "after":
                        $result->where("created > ?", $paramValue);
                        break;
                }
            }
        }

        return new Util\EntityStream("Comment", $result->order("$sort"));
    }
}
