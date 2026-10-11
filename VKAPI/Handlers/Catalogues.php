<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Search\Catalogue;

final class Catalogues extends VKAPIRequestHandler
{
    public function get(int $offset = 0, int $count = 10): object
    {
        $this->requireUser();

        $response = (object) [];

        return $response;
    }

    public function fetch(int $offset = 0, int $count = 10)
    {

    }

    public function create(string $name, string $description = "", int $private = 1): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
    }

    public function edit()
    {
        $this->requireUser();

    }

    public function delete()
    {
        $this->requireUser();

    }

    public function addTo()
    {
        $this->requireUser();

    }

    public function removeFrom()
    {
        $this->requireUser();

    }
}
