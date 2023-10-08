<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Notifications\PostAcceptedNotification;
use openvk\Web\Models\Repositories\{Posts, Notes};
use openvk\Web\Models\Repositories\{Posts, Notes, Videos};

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
        $this->videos = new Videos;
    }
    
    function getPost(int $id, callable $resolve, callable $reject): void
    {
        $post = $this->posts->get($id);
        if(!$post || $post->isDeleted())
            $reject(53, "No post with id=$id");

        if($post->getSuggestionType() != 0)
            $reject(25, "Can't get suggested post");
        
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
  
    function declinePost(int $id, callable $resolve, callable $reject)
    {
        $post = $this->posts->get($id);
        if(!$post || $post->isDeleted())
            $reject(11, "No post with id=$id");
        
        if($post->getSuggestionType() == 0)
            $reject(19, "Post is not suggested");

        if($post->getSuggestionType() == 2)
            $reject(10, "Post is already declined");

        if(!$post->canBePinnedBy($this->user))
            $reject(22, "Access to post denied");

        $post->setSuggested(2);
        $post->setDeleted(1);
        $post->save();

        $resolve($this->posts->getSuggestedPostsCount($post->getWallOwner()->getId()));
    }

    function acceptPost(int $id, bool $sign, string $content, callable $resolve, callable $reject)
    {
        $post = $this->posts->get($id);
        if(!$post || $post->isDeleted())
            $reject(11, "No post with id=$id");
        
        if($post->getSuggestionType() == 0)
            $reject(19, "Post is not suggested");

        if($post->getSuggestionType() == 2)
            $reject(10, "Post is declined");

        if(!$post->canBePinnedBy($this->user))
            $reject(22, "Access to post denied");

        $author = $post->getOwner();
        $flags = 0;
        $flags |= 0b10000000;

        if($sign)
            $flags |= 0b01000000;
        
        $post->setSuggested(0);
        $post->setCreated(time());
        $post->setApi_Source_Name(NULL);
        $post->setFlags($flags);

        if(mb_strlen($content) > 0)
            $post->setContent($content);
        
        $post->save();

        if($author->getId() != $this->user->getId())
            (new PostAcceptedNotification($author, $post, $post->getWallOwner()))->emit();
    
        $resolve(["id" => $post->getPrettyId(), "new_count" => $this->posts->getSuggestedPostsCount($post->getWallOwner()->getId())]);
    }
  
    function getVideos(int $page = 1, callable $resolve, callable $reject)
    {
        $videos = $this->videos->getByUser($this->user, $page, 8);
        $count  = $this->videos->getUserVideosCount($this->user);

        $arr = [
            "count"  => $count,
            "items"  => [],
        ];

        foreach($videos as $video) {
            $res = json_decode(json_encode($video->toVkApiStruct()), true);
            $res["video"]["author_name"] = $video->getOwner()->getCanonicalName();

            $arr["items"][] = $res;
        }

        $resolve($arr);
    }

    function searchVideos(int $page = 1, string $query, callable $resolve, callable $reject)
    {
        $dbc    = $this->videos->find($query);
        $videos = $dbc->page($page, 8);
        $count  = $dbc->size();

        $arr = [
            "count"  => $count,
            "items"  => [],
        ];

        foreach($videos as $video) {
            $res = json_decode(json_encode($video->toVkApiStruct()), true);
            $res["video"]["author_name"] = $video->getOwner()->getCanonicalName();
            
            $arr["items"][] = $res;
        }

        $resolve($arr);
    }
}
