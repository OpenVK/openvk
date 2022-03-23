<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Repositories\Audios;
use openvk\Web\Models\RowModel;

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

    private $importTable;

    const MAX_COUNT = 1000;
    const MAX_ITEMS = 10000;

    function __construct(?ActiveRow $ar = NULL)
    {
        parent::__construct($ar);

        $this->importTable = DatabaseConnection::i()->getContext()->table("playlist_imports");
    }

    function getCoverURL(): ?string
    {
        return NULL;
    }

    function getAudios(int $offset = 0, ?int $limit = NULL, ?int $shuffleSeed = NULL): \Traversable
    {
        if(!$shuffleSeed)
            return $this->fetchClassic($offset, $limit);

        $ids = [];
        foreach($this->relations->select("media AS i")->where("collection", $this->getId()) as $rel)
            $ids[] = $rel->i;

        $ids = knuth_shuffle($ids, $shuffleSeed);
        $ids = array_slice($ids, $offset, $limit ?? OPENVK_DEFAULT_PER_PAGE);
        foreach($ids as $id)
            yield (new Audios)->get($id);
    }

    function add(RowModel $audio): bool
    {
        if($res = parent::add($audio)) {
            $this->stateChanges("length", $this->getRecord()->length + $audio->getLength());
            $this->save();
        }

        return $res;
    }

    function remove(RowModel $audio): bool
    {
        if($res = parent::remove($audio)) {
            $this->stateChanges("length", $this->getRecord()->length - $audio->getLength());
            $this->save();
        }

        return $res;
    }

    function isBookmarkedBy(RowModel $entity): bool
    {
        $id = $entity->getId();
        if($entity instanceof Club)
            $id *= -1;

        return !is_null($this->importTable->where([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ])->fetch());
    }

    function bookmark(RowModel $entity): bool
    {
        if($this->isBookmarkedBy($entity))
            return false;

        $id = $entity->getId();
        if($entity instanceof Club)
            $id *= -1;

        if($this->importTable->where("entity", $id)->count > self::MAX_COUNT)
            throw new \OutOfBoundsException("Maximum amount of playlists");

        $this->importTable->insert([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ]);

        return true;
    }

    function unbookmark(RowModel $entity): bool
    {
        $id = $entity->getId();
        if($entity instanceof Club)
            $id *= -1;

        $count = $this->importTable->where([
            "entity"   => $id,
            "playlist" => $this->getId(),
        ])->delete();

        return $count > 0;
    }

    function setLength(): void
    {
        throw new \LogicException("Can't set length of playlist manually");
    }

    function delete(bool $softly = true): void
    {
        $ctx = DatabaseConnection::i()->getContext();
        $ctx->table("playlist_imports")->where("playlist", $this->getId())
            ->delete();

        parent::delete($softly);
    }
}