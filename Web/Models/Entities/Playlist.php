<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Repositories\Audios;
use openvk\Web\Models\Repositories\Photos;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\Photo;

/**
 * @method setName(string $name)
 * @method setDescription(?string $desc)
 */
class Playlist extends MediaCollection
{
    protected $tableName       = "playlists";
    protected $relTableName    = "playlist_relations";
    protected $entityTableName = "audios";
    protected $entityClassName = 'openvk\Web\Models\Entities\Audio';
    protected $allowDuplicates = false;

    private $importTable;

    public const MAX_COUNT = 1000;
    public const MAX_ITEMS = 10000;

    public function __construct(?ActiveRow $ar = null)
    {
        parent::__construct($ar);

        $this->importTable = DatabaseConnection::i()->getContext()->table("playlist_imports");
    }

    public function getCoverURL(string $size = "normal"): ?string
    {
        $photo = (new Photos())->get((int) $this->getRecord()->cover_photo_id);
        return is_null($photo) ? "/assets/packages/static/openvk/img/song.jpg" : $photo->getURLBySizeId($size);
    }

    public function getLength(): int
    {
        return $this->getRecord()->length;
    }

    public function fetchClassic(int $offset = 0, ?int $limit = null): \Traversable
    {
        $related = $this->getRecord()->related("$this->relTableName.collection")
            ->limit($limit ?? OPENVK_DEFAULT_PER_PAGE, $offset)
            ->order("index ASC");

        foreach ($related as $rel) {
            $media = $rel->ref($this->entityTableName, "media");
            if (!$media) {
                continue;
            }

            yield new $this->entityClassName($media);
        }
    }

    public function getAudios(int $offset = 0, ?int $limit = null, ?int $shuffleSeed = null): \Traversable
    {
        if (!$shuffleSeed) {
            foreach ($this->fetchClassic($offset, $limit) as $e) {
                yield $e;
            } # No, I can't return, it will break with []

            return;
        }

        $ids = [];
        foreach ($this->relations->select("media AS i")->where("collection", $this->getId()) as $rel) {
            $ids[] = $rel->i;
        }

        $ids = knuth_shuffle($ids, $shuffleSeed);
        $ids = array_slice($ids, $offset, $limit ?? OPENVK_DEFAULT_PER_PAGE);
        foreach ($ids as $id) {
            yield (new Audios())->get($id);
        }
    }

    public function add(RowModel $audio): bool
    {
        if ($res = parent::add($audio)) {
            $this->stateChanges("length", $this->getRecord()->length + $audio->getLength());
            $this->save();
        }

        return $res;
    }

    public function remove(RowModel $audio): bool
    {
        if ($res = parent::remove($audio)) {
            $this->stateChanges("length", $this->getRecord()->length - $audio->getLength());
            $this->save();
        }

        return $res;
    }

    public function isBookmarkedBy(RowModel $entity): bool
    {
        $id = $entity->getId();
        if ($entity instanceof Club) {
            $id *= -1;
        }

        return !is_null($this->importTable->where([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ])->fetch());
    }

    public function bookmark(RowModel $entity): bool
    {
        if ($this->isBookmarkedBy($entity)) {
            return false;
        }

        $id = $entity->getId();
        if ($entity instanceof Club) {
            $id *= -1;
        }

        if ($this->importTable->where("entity", $id)->count() > self::MAX_COUNT) {
            throw new \OutOfBoundsException("Maximum amount of playlists");
        }

        $this->importTable->insert([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ]);

        return true;
    }

    public function unbookmark(RowModel $entity): bool
    {
        $id = $entity->getId();
        if ($entity instanceof Club) {
            $id *= -1;
        }

        $count = $this->importTable->where([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ])->delete();

        return $count > 0;
    }

    public function getDescription(): ?string
    {
        return $this->getRecord()->description;
    }

    public function getDescriptionHTML(): ?string
    {
        return htmlspecialchars($this->getRecord()->description, ENT_DISALLOWED | ENT_XHTML);
    }

    public function getListens()
    {
        return $this->getRecord()->listens;
    }

    public function toVkApiStruct(?User $user = null): object
    {
        $oid = $this->getOwner()->getId();
        if ($this->getOwner() instanceof Club) {
            $oid *= -1;
        }

        return (object) [
            "id"          => $this->getId(),
            "owner_id"    => $oid,
            "title"       => $this->getName(),
            "description" => $this->getDescription(),
            "size"        => $this->size(),
            "length"      => $this->getLength(),
            "created"     => $this->getCreationTime()->timestamp(),
            "modified"    => $this->getEditTime() ? $this->getEditTime()->timestamp() : null,
            "accessible"  => $this->canBeViewedBy($user),
            "editable"    => $this->canBeModifiedBy($user),
            "bookmarked"  => $this->isBookmarkedBy($user),
            "listens"     => $this->getListens(),
            "cover_url"   => $this->getCoverURL(),
            "searchable"  => !$this->isUnlisted(),
        ];
    }

    public function setLength(): void
    {
        throw new \LogicException("Can't set length of playlist manually");
    }

    public function resetLength(): bool
    {
        $this->stateChanges("length", 0);

        return true;
    }

    public function delete(bool $softly = true): void
    {
        $ctx = DatabaseConnection::i()->getContext();
        $ctx->table("playlist_imports")->where("playlist", $this->getId())
            ->delete();

        parent::delete($softly);
    }

    public function hasAudio(Audio $audio): bool
    {
        $ctx = DatabaseConnection::i()->getContext();
        return !is_null($ctx->table("playlist_relations")->where([
            "collection" => $this->getId(),
            "media"      => $audio->getId(),
        ])->fetch());
    }

    public function getCoverPhotoId(): ?int
    {
        return $this->getRecord()->cover_photo_id;
    }

    public function getCoverPhoto(): ?Photo
    {
        return (new Photos())->get((int) $this->getRecord()->cover_photo_id);
    }

    public function canBeModifiedBy(User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->getOwner() instanceof User) {
            return $user->getId() == $this->getOwner()->getId();
        } else {
            return $this->getOwner()->canBeModifiedBy($user);
        }
    }

    public function getLengthInMinutes(): int
    {
        return (int) round($this->getLength() / 60, PHP_ROUND_HALF_DOWN);
    }

    public function fastMakeCover(int $owner, array $file)
    {
        $cover = new Photo();
        $cover->setOwner($owner);
        $cover->setDescription("Playlist cover image");
        $cover->setFile($file);
        $cover->setCreated(time());
        $cover->setSystem(true);
        $cover->save();

        $this->setCover_photo_id($cover->getId());

        return $cover;
    }

    public function getURL(): string
    {
        return "/playlist" . $this->getOwner()->getRealId() . "_" . $this->getId();
    }

    public function incrementListens()
    {
        $this->stateChanges("listens", ($this->getListens() + 1));
    }

    public function getMetaDescription(): string
    {
        $length = $this->getLengthInMinutes();

        $props = [];
        $props[] = tr("audios_count", $this->size());
        $props[] = "<span id='listensCount'>" . tr("listens_count", $this->getListens()) . "</span>";
        if ($length > 0) {
            $props[] = tr("minutes_count", $length);
        }
        $props[] = tr("created_playlist") . " " . $this->getPublicationTime();
        # if($this->getEditTime()) $props[] = tr("updated_playlist") . " " . $this->getEditTime();

        return implode(" • ", $props);
    }

    public function isUnlisted(): bool
    {
        return (bool) $this->getRecord()->unlisted;
    }
}
