<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Repositories\Notes as NotesRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;
use openvk\Web\Models\Entities\{Note, Comment};
use openvk\VKAPI\Structures\{Comment as APIComment};

final class Notes extends VKAPIRequestHandler
{
    function add(string $title, string $text, int $privacy = 0, int $comment_privacy = 0, string $privacy_view  = "", string $privacy_comment  = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $note = new Note;
        $note->setOwner($this->getUser()->getId());
        $note->setCreated(time());
        $note->setName($title);
        $note->setSource($text);
        $note->setEdited(time());
        $note->save();

        return $note->getVirtualId();
    }

    function createComment(string $note_id, int $owner_id, string $message, int $reply_to = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $note = (new NotesRepo)->getNoteById((int)$owner_id, (int)$note_id);

        if(!$note)
            $this->fail(180, "Note not found");
        if($note->isDeleted())
            $this->fail(189, "Note is deleted");

        $comment = new Comment;
        $comment->setOwner($this->getUser()->getId());
        $comment->setModel(get_class($note));
        $comment->setTarget($note->getId());
        $comment->setContent($message);
        $comment->setCreated(time());
        $comment->save();

        return $comment->getId();
    }

    function delete(string $note_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $note = (new NotesRepo)->get((int)$note_id);

        if(!$note)
            $this->fail(180, "Note not found");
        
        if(!$note->canBeModifiedBy($this->getUser()))
            $this->fail(15, "Access to note denied");
        
        $note->delete();

        return 1;
    }

    function deleteComment(int $comment_id, int $owner_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $comment = (new CommentsRepo)->get($comment_id);

        if(!$comment || !$comment->canBeDeletedBy($this->getUser()))
            $this->fail(403, "Access to comment denied");

        $comment->delete();

        return 1;
    }

    function edit(string $note_id, string $title = "", string $text = "", int $privacy = 0, int $comment_privacy = 0, string $privacy_view  = "", string $privacy_comment  = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $note = (new NotesRepo)->getNoteById($this->getUser()->getId(), (int)$note_id);

        if(!$note)
            $this->fail(180, "Note not found");
        
        if($note->isDeleted())
            $this->fail(189, "Note is deleted");

        !empty($title) ? $note->setName($title) : NULL;
        !empty($text)  ? $note->setSource($text) : NULL;

        $note->setCached_Content(NULL);
        $note->setEdited(time());
        $note->save();

        return 1;
    }

    function editComment(int $comment_id, string $message, int $owner_id = NULL)
    {
        /*
        $this->requireUser();
        $this->willExecuteWriteAction();

        $comment = (new CommentsRepo)->get($comment_id);

        if($comment->getOwner() != $this->getUser()->getId())
            $this->fail(15, "Access to comment denied");
        
        $comment->setContent($message);
        $comment->setEdited(time());
        $comment->save();
        */
        
        return 1;
    }

    function get(int $user_id, string $note_ids = "", int $offset = 0, int $count = 10, int $sort = 0)
    {
        $this->requireUser();
        $user = (new UsersRepo)->get($user_id);

        if(!$user)
            $this->fail(15, "Invalid user");
        
        if(empty($note_ids)) {
            $notes = array_slice(iterator_to_array((new NotesRepo)->getUserNotes($user, 1, $count + $offset, $sort == 0 ? "ASC" : "DESC")), $offset);
            $nodez = (object) [
                "count" => (new NotesRepo)->getUserNotesCount((new UsersRepo)->get($user_id)), 
                "notes" => []
            ];
    
            foreach($notes as $note) {
                if($note->isDeleted()) continue;
                
                $nodez->notes[] = $note->toVkApiStruct();
            }
        } else {
            $notes = explode(',', $note_ids);

            foreach($notes as $note)
            {
                $id    = explode("_", $note);
    
                $items = [];
    
                $note = (new NotesRepo)->getNoteById((int)$id[0], (int)$id[1]);
                if($note) {
                    $nodez->notes[] = $note->toVkApiStruct();
                }
            }
        }

        return $nodez;
    }

    function getById(int $note_id, int $owner_id, bool $need_wiki = false)
    {
        $this->requireUser();

        $note = (new NotesRepo)->getNoteById($owner_id, $note_id);

        if(!$note)
            $this->fail(180, "Note not found");
        
        if($note->isDeleted())
            $this->fail(189, "Note is deleted");
        
        if(!$note->getOwner() || $note->getOwner()->isDeleted())
            $this->fail(177, "Owner does not exists");

        return $note->toVkApiStruct();
    }

    function getComments(int $note_id, int $owner_id, int $sort = 1, int $offset = 0, int $count = 100)
    {
        $note = (new NotesRepo)->getNoteById($owner_id, $note_id);

        if(!$note)
            $this->fail(180, "Note not found");
        
        if($note->isDeleted())
            $this->fail(189, "Note is deleted");
        
        if(!$note->getOwner())
            $this->fail(177, "Owner does not exists");
        
        $arr = (object) [
            "count" => $note->getCommentsCount(), 
            "comments" => []];
        $comments = array_slice(iterator_to_array($note->getComments(1, $count)), $offset);
        
        foreach($comments as $comment) {
            $comm            = new APIComment;
            $comm->id        = $comment->getId();
            $comm->uid       = $comment->getOwner()->getId();
            $comm->nid       = $note->getId();
            $comm->oid       = $note->getOwner()->getId();
            $comm->date      = $comment->getPublicationTime()->timestamp();
            $comm->message   = $comment->getText();
            $comm->reply_to  = 0;
            $arr->comments[] = $comm;
        }

        return $arr;
    }

    function getFriendsNotes(int $offset = 0, int $count = 0)
    {
        $this->fail(4, "Not implemented");
    }

    function restoreComment(int $comment_id = 0, int $owner_id = 0)
    {
        $this->fail(4, "Not implemented");
    }
}
