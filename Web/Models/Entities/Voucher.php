<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class Voucher extends RowModel
{
    protected $tableName = "coin_vouchers";

    public function getCoins(): int
    {
        return $this->getRecord()->coins;
    }

    public function getRating(): int
    {
        return $this->getRecord()->rating;
    }

    public function getToken(): string
    {
        return $this->getRecord()->token;
    }

    public function getFormattedToken(): string
    {
        $fmtTok = "";
        $token  = $this->getRecord()->token;
        foreach (array_chunk(str_split($token), 6) as $chunk) {
            $fmtTok .= implode("", $chunk) . "-";
        }

        return substr($fmtTok, 0, -1);
    }

    public function getRemainingUsages(): float
    {
        return (float) ($this->getRecord()->usages_left ?? INF);
    }

    public function getUsers(int $page = -1, ?int $perPage = null): \Traversable
    {
        $relations = $this->getRecord()->related("voucher_users.voucher");
        if ($page !== -1) {
            $relations = $relations->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        }

        foreach ($relations as $relation) {
            yield (new Users())->get($relation->user);
        }
    }

    public function isExpired(): bool
    {
        return $this->getRemainingUsages() < 1;
    }

    public function wasUsedBy(User $user): bool
    {
        $record = $this->getRecord()->related("voucher_users.voucher")->where("user", $user->getId());

        return sizeof($record) > 0;
    }

    public function willUse(User $user): bool
    {
        if ($this->wasUsedBy($user)) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        $this->setRemainingUsages($this->getRemainingUsages() - 1);
        DB::i()->getContext()->table("voucher_users")->insert([
            "voucher" => $this->getId(),
            "user"    => $user->getId(),
        ]);

        return true;
    }

    public function setRemainingUsages(float $usages): void
    {
        $this->stateChanges("usages_left", $usages === INF ? null : ((int) $usages));
        $this->save();
    }
}
