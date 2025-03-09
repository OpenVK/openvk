<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\ContentSearchRepository;

final class ContentSearchPresenter extends OpenVKPresenter
{
    protected $repo;

    public function __construct(ContentSearchRepository $repository)
    {
        $this->repo = $repository;
    }

    public function renderIndex(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->template->results = $this->repo->find([
                "query" => $this->postParam("query"),
            ]);
        }
    }
}
