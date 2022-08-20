<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\{BugReport};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class BugtrackerReports
{
    private $context;
    private $reports;
    private $reportsPerPage = 5;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->reports   = $this->context->table("bugs");
    }

    private function toReport(?ActiveRow $ar)
    {
        return is_null($ar) ? NULL : new BugReport($ar);
    }

    function get(int $id): ?BugReport
    {
        return $this->toReport($this->reports->get($id));
    }

    function getAllReports(int $page = 1): \Traversable
    {
        foreach($this->reports->where(["deleted" => NULL])->order("created DESC")->page($page, 5) as $report)
            yield new BugReport($report);
    }

    function getReports(int $product_id = 0, int $page = 1): \Traversable
    {
        foreach($this->reports->where(["deleted" => NULL, "product_id" => $product_id])->order("created DESC")->page($page, 5) as $report)
            yield new BugReport($report);
    }

    function getReportsCount(): int
    {
        return sizeof($this->reports->where(["deleted" => NULL]));
    }

    function getByReporter(int $reporter_id, int $page = 1): \Traversable
    {
        foreach($this->reports->where(["deleted" => NULL, "reporter" => $reporter_id])->order("created DESC")->page($page, 5) as $report)
            yield new BugReport($report);
    }

    function getCountByReporter(int $reporter_id)
    {
        return sizeof($this->reports->where(["deleted" => NULL, "reporter" => $reporter_id]));
    }

    function getSuccCountByReporter(int $reporter_id)
    {
        return sizeof($this->reports->where(["deleted" => NULL, "reporter" => $reporter_id, "status" => "<= 4"]));
    }
}