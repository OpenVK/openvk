<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Relationships;

use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\RowModel;
use Chandler\Database\DatabaseConnection;

class Blacklist
{
    private $entity;
    private $context;

    public function __construct(RowModel $entity)
    {
        $this->entity  = $entity;
        $this->context = DatabaseConnection::i()->getContext();
    }

    public function getEntity(): RowModel
    {
        return $this->entity;
    }

    public function isBanned(?RowModel $entity2): bool
    {
        if (!$entity2) {
            return false;
        }

        $relations = $this->context->table("blacklist_relations")->where([
            "author" => $this->entity->getRealId(),
            "target" => $entity2->getRealId(),
        ]);

        foreach ($relations as $rel) {
            if ($rel->until === null || ((int) $rel->until > time())) {
                return true;
            }
        }

        return false;
    }

    public function ban(RowModel $user, ?string $reason = null, ?int $until = null): void
    {
        $this->unban($user);

        $this->context->table("blacklist_relations")->insert([
            "author"  => $this->entity->getRealId(),
            "target"  => $user->getRealId(),
            "created" => time(),
            "reason"  => $reason,
            "until"   => $until,
        ]);
    }

    public function unban(RowModel $user): void
    {
        $this->context->table("blacklist_relations")->where([
            "author" => $this->entity->getRealId(),
            "target" => $user->getRealId(),
        ])->delete();
    }

    public function getBanned(int $offset = 0, int $limit = 20)
    {
        $now = time();
        $relations = $this->context->table("blacklist_relations")
            ->where("author", $this->entity->getRealId())
            ->where("until IS NULL OR until > ?", $now)
            ->order("created ASC")
            ->limit($limit, $offset);

        $users = [];
        foreach ($relations as $rel) {
            $user = (new Users())->get($rel->target);
            if (!$user || $user->isDeleted()) {
                continue;
            }

            $users[] = $user;
        }

        return $users;
    }

    public function getBannedCount(): int
    {
        return (int) $this->context->table("blacklist_relations")
            ->where("author", $this->entity->getRealId())
            ->where("until IS NULL OR until > ?", time())
            ->count("*");
    }
}
