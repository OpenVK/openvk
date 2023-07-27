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
}
