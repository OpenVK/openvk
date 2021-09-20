<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Reports;
use openvk\Web\Models\Repositories\Posts;
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
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

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
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

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
        // А ВОТ ЩА ДОДЕЛАЮ
        // апд 01:00 по мск доделал фронт вроде!!!!
        if(!$id)
            $this->notFound();
            
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(empty($this->postParam("type")) && empty($this->postParam('id'))) {
                $this->flashFail("err", tr("error"), tr("error_segmentation")); 
            }

            // At this moment, only Posts will be implemented
            if($this->postParam("type") == 'posts') {
                $post = (new Posts)->get(intval($this->postParam("id")));
                if(!$post) 
                    $this->flashFail("err", "Ага! Попался, гадёныш блядь!", "Нельзя отправить жалобу на несуществующий контент");

                $postDublicate = $this->reports->getByContentId($post->getId());
                if($postDublicate)
                    $this->flashFail("err", tr("error"), "На этот контент уже пожаловался один из пользователей");

                $report = new Report;
                $report->setUser_id($this->user->id);
                $note->setContent_id($this->postParam("id"));
                $note->setReason($this->postParam("reason"));
                $note->setCreated(time());
                $note->setType($this->postParam("type"));
                $note->save();
                
                $this->flashFail("suc", "Жалоба отправлена", "Скоро её рассмотрят модераторы");
            } else {
                $this->flashFail("err", "Пока низя", "Нельзя отправить жалобу на данный тип контент");
            }

        }
    }
    
    function renderBan(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $report = $this->report->get($id);
        if(!$report) $this->notFound();
        if($note->isDeleted()) $this->notFound();
        if(is_null($this->user))
            $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
        
        $report->banUser();
        $report->deleteContent();
        $this->flash("suc", "Смэрть...", "Пользователь успешно забанен.");
        $this->redirect("/report/list");
    }

    function renderDeleteContent(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $report = $this->report->get($id);
        if(!$report) $this->notFound();
        if($note->isDeleted()) $this->notFound();
        if(is_null($this->user))
            $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
        
        $report->deleteContent();
        $this->flash("suc", "Нехай живе!", "Контент удалён, а пользователю прилетело предупреждение.");
        $this->redirect("/report/list");
    }

    function renderIgnoreReport(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $report = $this->report->get($id);
        if(!$report) $this->notFound();
        if($note->isDeleted()) $this->notFound();
        if(is_null($this->user))
            $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
        
        $report->delete();
        $this->flash("suc", "Нехай живе!", "Жалоба проигнорирована.");
        $this->redirect("/report/list");
    }
}
