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
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $this->template->reports = $this->reports->getReports(0, (int)($this->queryParam("p") ?? 1));
        $this->template->count = $this->reports->getReportsCount();
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $this->queryParam("p") ?? 1,
            "amount"  => NULL,
            "perPage" => 15,
        ];
    }
    
    function renderView(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $report = $this->reports->get($id);
        if(!$report || $report->isDeleted())
            $this->notFound();
        
        $this->template->report = $report;
    }
    
    function renderCreate(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        if(!$id)
            exit(json_encode([ "error" => tr("error_segmentation") ]));

        // At this moment, only Posts will be implemented
        if(in_array($this->queryParam("type"), ["post", "photo", "video", "group"])) {
            $post = (new Posts)->get(intval($id));
            if(!$post)
                exit(json_encode([ "error" => "Unable to report nonexistent content" ]));

            $report = new Report;
            $report->setUser_id($this->user->id);
            $report->setTarget_id($id);
            $report->setType($this->queryParam("type"));
            $report->setReason($this->queryParam("reason"));
            $report->setCreated(time());
            $report->save();
            
            exit(json_encode([ "reason" => $this->queryParam("reason") ]));
        } else {
            exit(json_encode([ "error" => "Unable to submit a report on this content type" ]));
        }
    }
    
    function renderAction(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $report = $this->reports->get($id);
        if(!$report) $this->notFound();
        if($report->isDeleted()) $this->notFound();

        if($this->postParam("ban")) {
            if(is_null($this->user))
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
            
            $report->banUser();
            $report->deleteContent();
            $this->flash("suc", "Смэрть...", "Пользователь успешно забанен.");
        }else if($this->postParam("delete")){
            if(is_null($this->user))
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
            
            $report->deleteContent();
            $this->flash("suc", "Нехай живе!", "Контент удалён, а пользователю прилетело предупреждение.");
        }else if($this->postParam("ignore")){
            if(is_null($this->user))
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");
            
            $report->delete();
            $this->flash("suc", "Нехай живе!", "Жалоба проигнорирована.");
        }else if($this->postParam("banClubOwner")) {
            if($report->getContentType() != "group")
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");

            $club = $report->getContentObject();

            if(is_null($club))
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");

            $owner = $club->getOwner();
            $owner->ban("Banned by report. Ask Technical support for ban reason");

            $report->delete();
            $this->flash("suc", "Смэрть...", "Создатель сообщества успешно забанен.");
        }else if($this->postParam("banClub")) {
            if($report->getContentType() != "group")
                $this->flashFail("err", "Ошибка доступа", "Недостаточно прав для модификации данного ресурса.");

            $club = $report->getContentObject();
            $club->ban("Banned by report. Ask Technical support for ban reason");

            $report->delete();
            $this->flash("suc", "Смэрть...", "Сообщество успешно забанено.");
        }
        $this->redirect("/admin/reports");
    }
}
