<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\{SupportTemplateDir};

class SupportTemplatesDirs
{
    private $context;
    private $dirs;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->dirs    = $this->context->table("support_templates_dirs");
    }

    private function toDir(?ActiveRow $ar): ?SupportTemplateDir
    {
        return is_null($ar) ? NULL : new SupportTemplateDir($ar);
    }

    function get(int $id): ?SupportTemplateDir
    {
        return $this->toDir($this->dirs->get($id));
    }

    function getList(int $uid): \Traversable
    {
        foreach ($this->dirs->where("(owner=$uid OR is_public=1) AND deleted=0") as $dir)
            yield new SupportTemplateDir($dir);
    }
}
