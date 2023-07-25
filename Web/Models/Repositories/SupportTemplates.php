<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{SupportTemplate, SupportAgent};

class SupportTemplates
{
    private $context;
    private $templates;

    function __construct()
    {
        $this->context   = DatabaseConnection::i()->getContext();
        $this->templates = $this->context->table("support_templates");
    }

    private function toTemplate(?ActiveRow $ar): ?SupportTemplate
    {
        return is_null($ar) ? NULL : new SupportTemplate($ar);
    }

    function get(int $id): ?SupportTemplate
    {
        return $this->toTemplate($this->templates->get($id));
    }

    function getListByDirId(int $dir_id): \Traversable
    {
        foreach ($this->templates->where(["dir" => $dir_id, "deleted" => 0]) as $template)
            yield new SupportTemplate($template);
    }
}
