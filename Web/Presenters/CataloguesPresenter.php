<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Search\Catalogue;

final class CataloguesPresenter extends OpenVKPresenter
{
    public function renderIndex(): void
    {
        $this->notFound();
        $this->assertUserLoggedIn();

        $act = $this->queryParam("act");
        $isPost = $_SERVER["REQUEST_METHOD"] === "POST";

        $this->template->act = $act;
        $this->template->acts = [["imported", "Imported"], ["new", tr("create")]];

        switch ($act) {
            case "new":
                $this->template->_template = "Catalogues/New.latte";

                if ($isPost) {
                    $this->willExecuteWriteAction();
                }

                break;
            case "edit":
                break;
            case "view":
                break;
            default:
                $this->template->catalogues = Catalogue::getImported($this->user->identity);
                break;
        }
    }

    public function renderAction(): void
    {
        $this->notFound();
        $this->assertUserLoggedIn();
    }
}
