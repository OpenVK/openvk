<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Audio;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\Playlist;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Util\EntityStream;

class Audios
{
    private $context;
    private $audios;
    private $rels;
    private $playlists;
    private $playlistImports;

    const ORDER_NEW     = 0;
    const ORDER_POPULAR = 1;

    const VK_ORDER_NEW     = 0;
    const VK_ORDER_LENGTH  = 1;
    const VK_ORDER_POPULAR = 2;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->audios  = $this->context->table("audios");
        $this->rels    = $this->context->table("audio_relations");

        $this->playlists       = $this->context->table("playlists");
        $this->playlistImports = $this->context->table("playlist_imports");
    }

    function get(int $id): ?Audio
    {
        $audio = $this->audios->get($id);
        if(!$audio)
            return NULL;

        return new Audio($audio);
    }

    function getPlaylist(int $id): ?Playlist
    {
        $playlist = $this->playlists->get($id);
        if(!$playlist)
            return NULL;

        return new Playlist($playlist);
    }

    function getByOwnerAndVID(int $owner, int $vId): ?Audio
    {
        $audio = $this->audios->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();
        if(!$audio) return NULL;

        return new Audio($audio);
    }

    function getByEntityID(int $entity, int $offset = 0, ?int $limit = NULL, ?int& $deleted = nullptr): \Traversable
    {
        $limit ??= OPENVK_DEFAULT_PER_PAGE;
        $iter = $this->rels->where("entity", $entity)->limit($limit, $offset);
        foreach($iter as $rel) {
            $audio = $this->get($rel->audio);
            if(!$audio || $audio->isDeleted()) {
                $deleted++;
                continue;
            }

            yield $audio;
        }
    }

    function getPlaylistsByEntityId(int $entity, int $offset = 0, ?int $limit = NULL, ?int& $deleted = nullptr): \Traversable
    {
        $limit ??= OPENVK_DEFAULT_PER_PAGE;
        $iter    = $this->playlistImports->where("entity", $entity)->limit($limit, $offset);
        foreach($iter as $rel) {
            $playlist = $this->getPlaylist($rel->playlist);
            if(!$playlist || $playlist->isDeleted()) {
                $deleted++;
                continue;
            }

            yield $playlist;
        }
    }

    function getByUser(User $user, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($user->getId(), ($perPage * ($page - 1)), $perPage, $deleted);
    }

    function getByClub(Club $club, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($club->getId() * -1, ($perPage * ($page - 1)), $perPage, $deleted);
    }

    function getPlaylistsByUser(User $user, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getPlaylistsByEntityId($user->getId(), ($perPage * ($page - 1)), $perPage, $deleted);
    }

    function getPlaylistsByClub(Club $club, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getPlaylistsByEntityId($club->getId() * -1, ($perPage * ($page - 1)), $perPage, $deleted);
    }

    function getUserCollectionSize(User $user): int
    {
        return sizeof($this->rels->where("entity", $user->getId()));
    }

    function getClubCollectionSize(Club $club): int
    {
        return sizeof($this->rels->where("entity", $club->getId() * -1));
    }

    function getUserPlaylistsCount(User $user): int
    {
        return sizeof($this->playlistImports->where("entity", $user->getId()));
    }

    function getClubPlaylistsCount(Club $club): int
    {
        return sizeof($this->playlistImports->where("entity", $club->getId() * -1));
    }

    function getByUploader(User $user): EntityStream
    {
        $search = $this->audios->where([
            "owner"   => $user->getId(),
            "deleted" => 0,
        ]);

        return new EntityStream("Audio", $search);
    }

    function getGlobal(int $order, ?string $genreId = NULL): EntityStream
    {
        $search = $this->audios->where([
            "deleted"   => 0,
            "unlisted"  => 0,
            "withdrawn" => 0,
        ])->order($order == Audios::ORDER_NEW ? "created DESC" : "listens DESC");

        if(!is_null($genreId))
            $search = $search->where("genre", $genreId);

        return new EntityStream("Audio", $search);
    }

    function search(string $query, int $sortMode = 0, bool $performerOnly = false, bool $withLyrics = false): EntityStream
    {
        $columns = $performerOnly ? "performer" : "performer, name";
        $order   = (["created", "length", "listens"][$sortMode] ?? "") . "DESC";

        $search = $this->audios->where([
            "unlisted" => 0,
            "deleted"  => 0,
        ])->where("MATCH ($columns) AGAINST (? WITH QUERY EXPANSION)", $query)->order($order);

        if($withLyrics)
            $search = $search->where("lyrics IS NOT NULL");

        return new EntityStream("Audio", $search);
    }

    function searchPlaylists(string $query): EntityStream
    {
        $search = $this->audios->where([
            "deleted" => 0,
        ])->where("MATCH (title, description) AGAINST (? IN BOOLEAN MODE)", $query);

        return new EntityStream("Playlist", $search);
    }
}