<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Reports;
use openvk\Web\Models\Entities\Report;

final class ReportPresenter extends OpenVKPresenter
{
    private $reports;
    
    function __construct(Reports $reports)
    {
        $this->reports = $reports;
        
        parent::__construct();
    }
    
    function renderList(): void
    {
        $this->template->reports = $this->reports->getReports(0, (int)($this->queryParam("p") ?? 1));
        $this->template->count = $this->notes->getReportsCount();
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $this->queryParam("p") ?? 1,
            "amount"  => NULL,
            "perPage" => 15,
        ];
    }
    
    function renderView(int $id): void
    {
        $report = $this->reports->get($id);
        if(!$report || $note->isDeleted())
            $this->notFound();
        
        $this->template->report = $report;
    }
    
    function renderCreate(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        // ЛАПСКИЙ Я НЕ ДО КОНЦА ДОДЕЛАЛ Я ПРОСТО МЫТЬСЯ ПОШЁЛ
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
