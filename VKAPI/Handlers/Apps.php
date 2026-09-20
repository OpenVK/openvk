<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

final class Apps extends VKAPIRequestHandler
{
    private function emptyCatalog(): object
    {
        return (object) [
            "count"    => 0,
            "items"    => [],
            "apps"     => [],
            "profiles" => [],
            "groups"   => [],
        ];
    }

    public function getMiniAppsCatalog(int $limit = 0, string $start_from = "", string $ref = "", int $section_id = 0, string $fields = ""): object
    {
        $this->requireUser();

        return $this->emptyCatalog();
    }

    public function getMiniAppsCatalogSearch(string $query = "", int $limit = 0, string $start_from = "", string $fields = ""): object
    {
        $this->requireUser();

        return $this->emptyCatalog();
    }
}
