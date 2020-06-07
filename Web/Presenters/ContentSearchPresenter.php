<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\ContentSearchRepository;

final class ContentSearchPresenter extends OpenVKPresenter
{
    private $repo;
    
    function __construct(ContentSearchRepository $repo)
    {
        $this->repo = $repo;
    }
    
    function renderIndex(): void
    {
        if($_SERVER["REQUEST_METHOD"] === "POST")
        {
            $this->template->results = $repo->find([
                "query" => $this->postParam("query"),
            ]);
        }
    }
}
