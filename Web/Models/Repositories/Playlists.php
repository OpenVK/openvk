<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Repositories\Repository;

class Playlists extends Repository
{
    protected $tableName = "playlists";
    protected $modelName = "playlist";
}
