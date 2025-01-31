<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use openvk\Web\Models\Entities\Voucher;

class Vouchers extends Repository
{
    protected $tableName = "coin_vouchers";
    protected $modelName = "Voucher";

    public function getByToken(string $token, bool $withDeleted = false)
    {
        $voucher = $this->table->where([
            "token"   => $token,
            "deleted" => $withDeleted,
        ])->fetch();

        return $this->toEntity($voucher);
    }
}
