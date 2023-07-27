<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Posts, Notes};

class Wall implements Handler
{
    protected $user;
    protected $posts;
    protected $notes;
    
    function __construct(?User $user)
    {
        $this->user  = $user;
        $this->posts = new Posts;
        $this->notes = new Notes;
    }
    
    function getPost(int $id, callable $resolve, callable $reject): void
    {
        $post = $this->posts->get($id);
        if(!$post || $post->isDeleted())
            $reject("No post with id=$id");
        
        $res = (object) [];
        $res->id     = $post->getId();
        $res->wall   = $post->getTargetWall();
        $res->author = (($owner = $post->getOwner())) instanceof User
                       ? ($owner->getId())
                       : ($owner->getId() * -1);
        
        if($post->isSigned())
            $res->signedOffBy = $post->getOwnerPost();
        
        $res->pinned    = $post->isPinned();
        $res->sponsored = $post->isAd();
        $res->nsfw      = $post->isExplicit();
        $res->text      = $post->getText();
        
        $res->likes = [
            "count"   => $post->getLikesCount(),
            "hasLike" => $post->hasLikeFrom($this->user),
            "likedBy" => [],
        ];
        foreach($post->getLikers() as $liker) {
            $res->likes["likedBy"][] = [
                "id"     => $liker->getId(),
                "url"    => $liker->getURL(),
                "name"   => $liker->getCanonicalName(),
                "avatar" => $liker->getAvatarURL(),
            ];
        }
        
        $res->created = (string) $post->getPublicationTime();
        $res->canPin  = $post->canBePinnedBy($this->user);
        $res->canEdit = $res->canDelete = $post->canBeDeletedBy($this->user);
        
        $resolve((array) $res);
    }
    
    function newStatus(string $text, callable $resolve, callable $reject): void
    {
        $post = new Post;
        $post->setOwner($this->user->getId());
        $post->setWall($this->user->getId());
        $post->setCreated(time());
        $post->setContent($text);
        $post->setAnonymous(false);
        $post->setFlags(0);
        $post->setNsfw(false);
        $post->save();
        
        $resolve($post->getId());
    }

    function getMyNotes(callable $resolve, callable $reject)
    {
        $count   = $this->notes->getUserNotesCount($this->user);
        $myNotes = $this->notes->getUserNotes($this->user, 1, $count);

        $arr = [
            "count"  => $count,
            "closed" => $this->user->getPrivacySetting("notes.read"),
            "items"  => [],
        ];

        foreach($myNotes as $note) {
            $arr["items"][] = [
                "id"      => $note->getId(),
                "name"    => ovk_proc_strtr($note->getName(), 30),
                #"preview" => $note->getPreview() 
            ];
        }

        $resolve($arr);
    }
}
