<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\Postable;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\VKAPI\Handlers\Wall;

final class Newsfeed extends VKAPIRequestHandler
{
    function get(string $fields = "", int $start_from = 0, int $offset = 0, int $count = 30, int $extended = 0)
    {
        $this->requireUser();
        
        if($offset != 0) $start_from = $offset;
        
        $id    = $this->getUser()->getId();
        $subs  = DatabaseConnection::i()
                    ->getContext()
                    ->table("subscriptions")
                    ->where("follower", $id);
        $ids   = array_map(function($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));
        $ids[] = $this->getUser()->getId();
        
        $posts = DatabaseConnection::i()
                ->getContext()
                ->table("posts")
                ->select("id")
                ->where("wall IN (?)", $ids)
                ->where("deleted", 0)
                ->order("created DESC");

        $rposts = [];
        foreach($posts->page((int) ($offset + 1), $count) as $post)
            $rposts[] = (new PostsRepo)->get($post->id)->getPrettyId();

        return (new Wall)->getById(implode(',', $rposts), $extended, $fields, $this->getUser());
    }
}
