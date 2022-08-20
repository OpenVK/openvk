<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use Chandler\Session\Session;
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Repositories\{BugtrackerProducts, BugtrackerReports, BugtrackerComments, Users};
use openvk\Web\Models\Entities\{BugtrackerProduct, BugReport};

final class BugtrackerPresenter extends OpenVKPresenter
{
    private $reports;
    private $products;
    private $comments;
    
    function __construct(BugtrackerReports $reports, BugtrackerProducts $products, BugtrackerComments $comments)
    {
        $this->reports  = $reports;
        $this->products = $products;
        $this->comments = $comments;
        
        parent::__construct();
    }

    function renderIndex(): void
    {
        $this->assertUserLoggedIn();
        $this->template->mode = in_array($this->queryParam("act"), ["list", "show", "products", "new_product", "reporter", "new"]) ? $this->queryParam("act") : "list";

        $this->template->user = $this->user;
       
        $this->template->all_products = $this->products->getAll();
        $this->template->open_products = $this->products->getOpen();

        if($this->template->mode === "reporter") {
            $this->template->reporter = (new Users)->get((int) $this->queryParam("id"));
            $this->template->reporter_stats = [$this->reports->getCountByReporter((int) $this->queryParam("id")), $this->reports->getSuccCountByReporter((int) $this->queryParam("id"))];

            $this->template->page = (int) ($this->queryParam("p") ?? 1);
            $this->template->iterator = $this->reports->getByReporter((int) $this->queryParam("id"));
            $this->template->count = $this->reports->getCountByReporter((int) $this->queryParam("id"));
        } else {
            $this->template->page     = (int) ($this->queryParam("p") ?? 1);
            $this->template->count    = $this->reports->getReportsCount(0);
            $this->template->iterator = $this->reports->getAllReports($this->template->page);
        }

        $this->template->canAdminBugTracker = $this->user->identity->getChandlerUser()->can("admin")->model('openvk\Web\Models\Repositories\BugtrackerReports')->whichBelongsTo(NULL);
    }

    function renderView(int $id): void
    {
        $this->assertUserLoggedIn();

        $this->template->user = $this->user;

        if ($this->reports->get($id)) {
            $this->template->bug = $this->reports->get($id);
            $this->template->reporter = $this->template->bug->getReporter();
            $this->template->comments = $this->comments->getByReport($this->template->bug);

            $this->template->canAdminBugTracker = $this->user->identity->getChandlerUser()->can("admin")->model('openvk\Web\Models\Repositories\BugtrackerReports')->whichBelongsTo(NULL);
        } else {
            $this->flashFail("err", "Отчёт не найден. Возможно, он был удалён.");
        }
    }

    function renderChangeStatus(int $report_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $status = $this->postParam("status");
        $comment = $this->postParam("text");
        $list = ["Открыт", "На рассмотрении", "В работе", "Исправлен", "Закрыт", "Требует корректировки", "Заблокирован", "Отклонён"];

        $report = (new BugtrackerReports)->get($report_id);
        $report->setStatus($status);
        $report->save();

        $this->createComment($report, $comment, "Новый статус отчёта — $list[$status]", TRUE);
        $this->flashFail("succ", "Изменения сохранены", "Новый статус отчёта — $list[$status]");
    }

    function renderChangePriority(int $report_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $priority = $this->postParam("priority");
        $comment = $this->postParam("text");
        $list = ["Пожелание", "Низкий", "Средний", "Высокий", "Критический", "Уязвимость"];

        $report = (new BugtrackerReports)->get($report_id);
        $report->setPriority($priority);
        $report->save();

        $this->createComment($report, $comment, "Новый приоритет отчёта — $list[$priority]", TRUE);
        $this->flashFail("succ", "Изменения сохранены", "Новый приоритет отчёта — $list[$priority]");
    }

    function createComment(?BugReport $report, string $text, string $label = "", bool $is_moder = FALSE, bool $is_hidden = FALSE)
    {
        $moder = $this->user->identity->getChandlerUser()->can("admin")->model('openvk\Web\Models\Repositories\BugtrackerReports')->whichBelongsTo(NULL);

        if (!$text && !$label)
            $this->flashFail("err", "Ошибка", "Комментарий не может быть пустым.");

        if ($report->getRawStatus() == 6 && !$moder)
            $this->flashFail("err", "Ошибка доступа");

        DB::i()->getContext()->table("bt_comments")->insert([
            "report" => $report->getId(),
            "author" => $this->user->identity->getId(),
            "is_moder" => $moder === $is_moder,
            "is_hidden" => $moder === $is_hidden,
            "text" => $text,
            "label" => $label
        ]);

        $this->flashFail("succ", "Успех", "Комментарий отправлен.");
    }

    function renderAddComment(int $report_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $text = $this->postParam("text");
        $is_moder = (bool) $this->postParam("is_moder");
        $is_hidden = (bool) $this->postParam("is_hidden");

        $this->createComment($this->reports->get($report_id), $text, "", $is_moder, $is_hidden);
    }

    function renderCreate(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $title = $this->postParam("title");
        $text = $this->postParam("text");
        $priority = $this->postParam("priority");
        $product = $this->postParam("product");
        $device = $this->postParam("device");

        if (!$title || !$text || !$priority || !$product || !$device)
            $this->flashFail("err", "Ошибка", "Заполнены не все поля");
        
        $id = DB::i()->getContext()->table("bugs")->insert([
            "reporter" => $this->user->identity->getId(),
            "title" => $title,
            "text" => $text,
            "product_id" => $product,
            "device" => $device,
            "priority" => $priority,
            "created" => time()
        ]);

        $this->redirect("/bug$id");
    }

    function renderCreateProduct(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $moder = $this->user->identity->getChandlerUser()->can("admin")->model('openvk\Web\Models\Repositories\BugtrackerReports')->whichBelongsTo(NULL);

        if (!$moder)
            $this->flashFail("err", "Ошибка доступа");

        $title = $this->postParam("title");
        $description = $this->postParam("description");

        DB::i()->getContext()->table("bt_products")->insert([
            "creator_id" => $this->user->identity->getId(),
            "title" => $title,
            "description" => $description
        ]);

        $this->redirect("/bugtracker?act=products");
    }

    function renderReproduced(int $report_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $report = (new BugtrackerReports)->get($report_id);
        
        if ($report->getReporter()->getId() === $this->user->identity->getId())
            $this->flashFail("err", "Ошибка доступа");

        DB::i()->getContext()->table("bugs")->where("id", $report_id)->update("reproduced", $report->getReproducedCount() + 1);

        $this->flashFail("succ", "Успех", "Вы отметили, что у Вас получилось воспроизвести этот баг.");
    }
}