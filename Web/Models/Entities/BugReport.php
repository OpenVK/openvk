<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\{RowModel};
use openvk\Web\Models\Entities\{User, BugtrackerProduct};
use openvk\Web\Models\Repositories\{Users, BugtrackerProducts};
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Util\DateTime;

class BugReport extends RowModel
{
    protected $tableName = "bugs";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getReporter(): ?User
    {
        return (new Users)->get($this->getRecord()->reporter);
    }

    function getName(): string
    {
        return $this->getRecord()->title;
    }

    function getCanonicalName(): string
    {
        return $this->getName();
    }

    function getText(): string
    {
        return $this->getRecord()->text;
    }

    function getProduct(): ?BugtrackerProduct
    {
        return (new BugtrackerProducts)->get($this->getRecord()->product_id);
    }

    function getStatus(): string
    {
        $list = ["Открыт", "На рассмотрении", "В работе", "Исправлен", "Закрыт", "Требует корректировки", "Заблокирован", "Отклонён"];
        $status_id = $this->getRecord()->status;

        return $list[$status_id];
    }

    function getRawStatus(): ?int
    {
        return $this->getRecord()->status;
    }

    function getPriority(): string
    {
        $list = ["Пожелание", "Низкий", "Средний", "Высокий", "Критический", "Уязвимость"];
        $priority_id = $this->getRecord()->priority;
        
        return $list[$priority_id];
    }

    function getRawPriority(): ?int
    {
        return $this->getRecord()->priority;
    }

    function getDevice(): string
    {
        return $this->getRecord()->device;
    }

    function getReproducedCount(): ?int
    {
        return $this->getRecord()->reproduced;
    }

    function getCreationDate(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }
}