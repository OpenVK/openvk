<?php

declare(strict_types=1);

namespace openvk\Web\Models;

use Chandler\Database\DBEntity;

/*
 * NOTICE: Please use \Chandler\Database\DBEntity directly.
 */
abstract class RowModel extends DBEntity
{
    public function isGeoBlocked(bool $allowAvatar = false): bool
    {
        return \openvk\Web\Util\GeoBlock::i()->isBlocked($this, null, $allowAvatar);
    }
}
