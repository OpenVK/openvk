<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Documents;

final class DocumentsPresenter extends OpenVKPresenter
{
    protected $presenterName = "documents";
    protected $silent = true;

    function renderList(?int $gid = NULL): void
    {
        $this->template->_template = "Documents/List.xml";
    }

    function renderListGroup(?int $gid)
    {
        $this->renderList($gid);
    }
}
