<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Videos, Applications, Audios, Documents};
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;

class Catalogue extends RowModel
{
    protected $tableName= "catalogues";
    private $user;
}
