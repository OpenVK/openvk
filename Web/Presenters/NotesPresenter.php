<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Notes;
use openvk\Web\Models\Entities\Note;

final class NotesPresenter extends OpenVKPresenter
{
    private $notes;
    
    function __construct(Notes $notes)
    {
        $this->notes = $notes;
        
        parent::__construct();
    }
    
    function renderList(int $owner): void
    {
        $user = (new Users)->get($owner);
        if(!$user) $this->notFound();
        
        $this->template->notes = $this->notes->getUserNotes($user, (int)($this->queryParam("p") ?? 1));
        $this->template->count = $this->notes->getUserNotesCount($user);
        $this->template->owner = $user;
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $this->queryParam("p") ?? 1,
            "amount"  => NULL,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
    }
    
    function renderView(int $owner, int $id): void
    {
        $note = $this->notes->get($id);
        if(!$note || $note->getOwner()->getId() !== $owner)
            $this->notFound();
        
        $this->template->cCount   = $note->getCommentsCount();
        $this->template->cPage    = (int) ($this->queryParam("p") ?? 1);
        $this->template->comments = iterator_to_array($note->getComments($this->template->cPage));
        $this->template->note     = $note;
    }
    
    function renderCreate(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $id = $this->user->id; #TODO: when ACL'll be done, allow admins to edit users via ?GUID=(chandler guid)
        
        if(!$id)
            $this->notFound();
            
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(empty($this->postParam("name"))) {
                $this->flashFail("err", tr("error"), tr("error_segmentation")); 
            }

            $note = new Note;
            $note->setOwner($this->user->id);
            $note->setCreated(time());
            $note->setName($this->postParam("name"));
            $note->setSource($this->postParam("html"));
            $note->save();
            
            $this->redirect("/note" . $this->user->id . "_" . $note->getId());
        }
    }
    
    function renderDelete(int $owner, int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();
        
        $note = $this->notes->get($id);
        if(!$note) $this->notFound();
        if($note->getOwner()->getId() . "_" . $note->getId() !== $owner . "_" . $id || $note->isDeleted()) $this->notFound();
        if(is_null($this->user) || !$note->canBeModifiedBy($this->user->identity))
            $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
        
        $name = $note->getName();
        $note->delete();
        $this->flash("succ", "Заметка удалена", "Заметка \"$name\" была успешно удалена.");
        $this->redirect("/notes" . $this->user->id);
    }
}
