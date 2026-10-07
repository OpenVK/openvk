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

    private static array $relations = [];

    public function __construct(RowModel $entity)
    {
        $this->entity  = $entity;
        $this->context = DatabaseConnection::i()->getContext();
    }

    private static function fetchRelations(int $author, int $target): array
    {
        $key = "$author:$target";
        if (!array_key_exists($key, self::$relations)) {
            self::$relations[$key] = DatabaseConnection::i()
                ->getContext()
                ->table("blacklist_relations")
                ->where(["author" => $author, "target" => $target])
                ->fetchAll();
        }

        return self::$relations[$key];
    }

    public static function forgetRelations(int $author, int $target): void
    {
        unset(self::$relations["$author:$target"]);
    }

    public static function isRelated(int $author, int $target): bool
    {
        return sizeof(self::fetchRelations($author, $target)) > 0;
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

        foreach (self::fetchRelations($this->entity->getRealId(), $entity2->getRealId()) as $rel) {
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

        self::forgetRelations($this->entity->getRealId(), $user->getRealId());
    }

    public function unban(RowModel $user): void
    {
        $this->context->table("blacklist_relations")->where([
            "author" => $this->entity->getRealId(),
            "target" => $user->getRealId(),
        ])->delete();

        self::forgetRelations($this->entity->getRealId(), $user->getRealId());
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
