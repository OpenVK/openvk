<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Comments;
use Chandler\Database\DatabaseConnection as DB;
use Nette\InvalidStateException as ISE;
use Nette\Database\Table\Selection;

abstract class Postable extends Attachable
{
    use Traits\TAttachmentHost;
    use Traits\TOwnable;
    /**
    * Column name, that references to an object, that
    * is hieararchically higher than this Postable.
    *
    * For example: Images belong to User, but Posts belong to Wall.
    * Formally users still own posts, but walls also own posts and they are
    * their direct parent.
    *
    * @var string
    */
    protected $upperNodeReferenceColumnName = "owner";

    private function getTable(): Selection
    {
        return DB::i()->getContext()->table($this->tableName);
    }

    public function getOwner(bool $real = false): RowModel
    {
        $oid = (int) $this->getRecord()->owner;
        if (!$real && $this->isAnonymous()) {
            $oid = (int) OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["account"];
        }

        $oid = abs($oid);
        if ($oid > 0) {
            return (new Users())->get($oid);
        } else {
            return (new Clubs())->get($oid * -1);
        }
    }

    public function getVirtualId(): int
    {
        return $this->getRecord()->virtual_id;
    }

    public function getPrettyId(): string
    {
        return $this->getRecord()->owner . "_" . $this->getVirtualId();
    }

    public function getPublicationTime(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }

    public function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        if (is_null($edited)) {
            return null;
        }

        return new DateTime($edited);
    }

    public function getComments(int $page, ?int $perPage = null, string $sort = "ASC"): \Traversable
    {
        return (new Comments())->getCommentsByTarget($this, $page, $perPage, $sort);
    }

    public function getCommentsCount(): int
    {
        return (new Comments())->getCommentsCountByTarget($this);
    }

    public function getLastComments(int $count): \Traversable
    {
        return (new Comments())->getLastCommentsByTarget($this, $count);
    }

    public function getLikesCount(): int
    {
        return sizeof(DB::i()->getContext()->table("likes")->where([
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ])->group("origin"));
    }

    public function getLikers(int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;

        $sel = DB::i()->getContext()->table("likes")->where([
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ])->page($page, $perPage);

        foreach ($sel as $like) {
            $user = (new Users())->get($like->origin);
            if ($user->isPrivateLikes() && OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["enable"]) {
                $user = (new Users())->get((int) OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["account"]);
            }

            yield $user;
        }
    }

    public function getAccessKey(): string
    {
        return $this->getRecord()->access_key;
    }

    public function checkAccessKey(?string $access_key): bool
    {
        if ($this->getAccessKey() === $access_key) {
            return true;
        }

        return !$this->isPrivate();
    }

    public function isPrivate(): bool
    {
        return (bool) $this->getRecord()->unlisted;
    }

    public function isAnonymous(): bool
    {
        return (bool) $this->getRecord()->anonymous;
    }

    public function toggleLike(User $user): bool
    {
        $searchData = [
            "origin" => $user->getId(),
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ];

        if (sizeof(DB::i()->getContext()->table("likes")->where($searchData)) > 0) {
            DB::i()->getContext()->table("likes")->where($searchData)->delete();
            return false;
        }

        DB::i()->getContext()->table("likes")->insert($searchData);
        return true;
    }

    public function setLike(bool $liked, User $user): void
    {
        $searchData = [
            "origin" => $user->getId(),
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ];

        if ($liked) {
            if (!$this->hasLikeFrom($user)) {
                DB::i()->getContext()->table("likes")->insert($searchData);
            }
        } else {
            if ($this->hasLikeFrom($user)) {
                DB::i()->getContext()->table("likes")->where($searchData)->delete();
            }
        }
    }

    public function hasLikeFrom(User $user): bool
    {
        $searchData = [
            "origin" => $user->getId(),
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ];

        return sizeof(DB::i()->getContext()->table("likes")->where($searchData)) > 0;
    }

    public function setVirtual_Id(int $id): void
    {
        throw new ISE("Setting virtual id manually is forbidden");
    }

    public function save(?bool $log = false): void
    {
        $vref = $this->upperNodeReferenceColumnName;

        $vid  = $this->getRecord()->{$vref} ?? $this->changes[$vref];
        if (!$vid) {
            throw new ISE("Can't presist post due to inability to calculate it's $vref post count. Have you set it?");
        }

        $pCount = sizeof($this->getTable()->where($vref, $vid));
        if (is_null($this->getRecord())) {
            # lol allow ppl to taint created value
            if (!isset($this->changes["created"])) {
                $this->stateChanges("created", time());
            }

            $this->stateChanges("virtual_id", $pCount + 1);
        } /*else {
            $this->stateChanges("edited", time());
        }*/

        parent::save($log);
    }
}
