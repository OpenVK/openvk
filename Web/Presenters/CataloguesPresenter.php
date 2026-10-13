<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

final class CataloguesPresenter extends OpenVKPresenter
{
    public function renderIndex(): void
    {
        $this->assertUserLoggedIn();

        $act = $this->queryParam("act");
        $isPost = $_SERVER["REQUEST_METHOD"] === "POST";

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
        }
    }

    public function renderAction(): void
    {
        $this->assertUserLoggedIn();
    }
}
