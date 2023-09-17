<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{Comment, Notifications\MentionNotification, Photo, Video, User, Topic, Post};
use openvk\Web\Models\Entities\Notifications\CommentNotification;
use openvk\Web\Models\Repositories\{Comments, Clubs, Videos};

final class CommentPresenter extends OpenVKPresenter
{
    protected $presenterName = "comment";
    private $models = [
        "posts"   => "openvk\\Web\\Models\\Repositories\\Posts",
        "photos"  => "openvk\\Web\\Models\\Repositories\\Photos",
        "videos"  => "openvk\\Web\\Models\\Repositories\\Videos",
        "notes"   => "openvk\\Web\\Models\\Repositories\\Notes",
        "topics"  => "openvk\\Web\\Models\\Repositories\\Topics",
    ];
    
    function renderLike(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $comment = (new Comments)->get($id);
        if(!$comment || $comment->isDeleted()) $this->notFound();

        if ($comment->getTarget() instanceof Post && $comment->getTarget()->getWallOwner()->isBanned())
            $this->flashFail("err", tr("error"), tr("forbidden"));
        
        if(!is_null($this->user)) $comment->toggleLike($this->user->identity);
        
        $this->redirect($_SERVER["HTTP_REFERER"]);
    }
    
    function renderMakeComment(string $repo, int $eId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $repoClass = $this->models[$repo] ?? NULL;
        if(!$repoClass) chandler_http_panic(400, "Bad Request", "Unexpected $repo.");
        
        $repo   = new $repoClass;
        $entity = $repo->get($eId);
        if(!$entity) $this->notFound();

        if($entity instanceof Topic && $entity->isClosed())
            $this->notFound();

        if($entity instanceof Post && $entity->getTargetWall() < 0)
            $club = (new Clubs)->get(abs($entity->getTargetWall()));
        else if($entity instanceof Topic)
            $club = $entity->getClub();

        if ($entity instanceof Post && $entity->getWallOwner()->isBanned())
            $this->flashFail("err", tr("error"), tr("forbidden"));

        if($_FILES["_vid_attachment"] && OPENVK_ROOT_CONF['openvk']['preferences']['videos']['disableUploading'])
            $this->flashFail("err", tr("error"), tr("video_uploads_disabled"));
        
        $flags = 0;
        if($this->postParam("as_group") === "on" && !is_null($club) && $club->canBeModifiedBy($this->user->identity))
            $flags |= 0b10000000;

        $photo = NULL;
        if($_FILES["_pic_attachment"]["error"] === UPLOAD_ERR_OK) {
            try {
                $photo = Photo::fastMake($this->user->id, $this->postParam("text"), $_FILES["_pic_attachment"]);
            } catch(ISE $ex) {
                $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_when_publishing_comment_description"));
            }
        }
        
        # TODO move to trait
        try {
            $photo = NULL;
            if($_FILES["_pic_attachment"]["error"] === UPLOAD_ERR_OK) {
                $album = NULL;
                if($wall > 0 && $wall === $this->user->id)
                    $album = (new Albums)->getUserWallAlbum($wallOwner);
                
                $photo = Photo::fastMake($this->user->id, $this->postParam("text"), $_FILES["_pic_attachment"], $album);
            }
        } catch(ISE $ex) {
            $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_comment_file_too_big"));
        }

        $videos = [];

        if(!empty($this->postParam("videos"))) {
            $un  = rtrim($this->postParam("videos"), ",");
            $arr = explode(",", $un);
            
            if(sizeof($arr) < 11) {
                foreach($arr as $dat) {
                    $ids = explode("_", $dat);
                    $video = (new Videos)->getByOwnerAndVID((int)$ids[0], (int)$ids[1]);
    
                    if(!$video || $video->isDeleted())
                        continue;
    
                    $videos[] = $video;
                }
            }
        }
        
        if(empty($this->postParam("text")) && !$photo && !$video)
            $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_comment_empty"));
        
        try {
            $comment = new Comment;
            $comment->setOwner($this->user->id);
            $comment->setModel(get_class($entity));
            $comment->setTarget($entity->getId());
            $comment->setContent($this->postParam("text"));
            $comment->setCreated(time());
            $comment->setFlags($flags);
            $comment->save();
        } catch (\LengthException $ex) {
            $this->flashFail("err", tr("error_when_publishing_comment"), tr("error_comment_too_big"));
        }
        
        if(!is_null($photo))
            $comment->attach($photo);
        
        if(sizeof($videos) > 0)
            foreach($videos as $vid)
                $comment->attach($vid);
        
        if($entity->getOwner()->getId() !== $this->user->identity->getId())
            if(($owner = $entity->getOwner()) instanceof User)
                (new CommentNotification($owner, $comment, $entity, $this->user->identity))->emit();
    
        $excludeMentions = [$this->user->identity->getId()];
        if(($owner = $entity->getOwner()) instanceof User)
            $excludeMentions[] = $owner->getId();

        $mentions = iterator_to_array($comment->resolveMentions($excludeMentions));
        foreach($mentions as $mentionee)
            if($mentionee instanceof User)
                (new MentionNotification($mentionee, $entity, $comment->getOwner(), strip_tags($comment->getText())))->emit();
        
        $this->flashFail("succ", tr("comment_is_added"), tr("comment_is_added_desc"));
    }
    
    function renderDeleteComment(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $comment = (new Comments)->get($id);
        if(!$comment) $this->notFound();
        if(!$comment->canBeDeletedBy($this->user->identity))
            $this->throwError(403, "Forbidden", tr("error_access_denied"));
        if ($comment->getTarget() instanceof Post && $comment->getTarget()->getWallOwner()->isBanned())
            $this->flashFail("err", tr("error"), tr("forbidden"));

        $comment->delete();
        $this->flashFail(
            "succ",
            tr("success"),
            tr("comment_will_not_appear")
        );
    }
}
