<?php

declare(strict_types=1);

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
    private $playlistRels;

    public const ORDER_NEW     = 0;
    public const ORDER_POPULAR = 1;

    public const VK_ORDER_NEW     = 0;
    public const VK_ORDER_LENGTH  = 1;
    public const VK_ORDER_POPULAR = 2;

    public function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->audios  = $this->context->table("audios");
        $this->rels    = $this->context->table("audio_relations");

        $this->playlists       = $this->context->table("playlists");
        $this->playlistImports = $this->context->table("playlist_imports");
        $this->playlistRels    = $this->context->table("playlist_relations");
    }

    public function get(int $id): ?Audio
    {
        $audio = $this->audios->get($id);
        if (!$audio) {
            return null;
        }

        return new Audio($audio);
    }

    public function getPlaylist(int $id): ?Playlist
    {
        $playlist = $this->playlists->get($id);
        if (!$playlist) {
            return null;
        }

        return new Playlist($playlist);
    }

    public function getByOwnerAndVID(int $owner, int $vId): ?Audio
    {
        $audio = $this->audios->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
        ])->fetch();
        if (!$audio) {
            return null;
        }

        return new Audio($audio);
    }

    public function getPlaylistByOwnerAndVID(int $owner, int $vId): ?Playlist
    {
        $playlist = $this->playlists->where([
            "owner" => $owner,
            "id"    => $vId,
        ])->fetch();
        if (!$playlist) {
            return null;
        }

        return new Playlist($playlist);
    }

    public function getByEntityID(int $entity, int $offset = 0, ?int $limit = null, ?int& $deleted = nullptr): \Traversable
    {
        $limit ??= OPENVK_DEFAULT_PER_PAGE;
        $iter = $this->rels->where("entity", $entity)->limit($limit, $offset)->order("index DESC");
        foreach ($iter as $rel) {
            $audio = $this->get($rel->audio);
            if (!$audio || $audio->isDeleted()) {
                $deleted++;
                continue;
            }

            yield $audio;
        }
    }

    public function getPlaylistsByEntityId(int $entity, int $offset = 0, ?int $limit = null, ?int& $deleted = nullptr): \Traversable
    {
        $limit ??= OPENVK_DEFAULT_PER_PAGE;
        $iter    = $this->playlistImports->where("entity", $entity)->limit($limit, $offset);
        foreach ($iter as $rel) {
            $playlist = $this->getPlaylist($rel->playlist);
            if (!$playlist || $playlist->isDeleted()) {
                $deleted++;
                continue;
            }

            yield $playlist;
        }
    }

    public function getByUser(User $user, int $page = 1, ?int $perPage = null, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($user->getId(), ($perPage * ($page - 1)), $perPage, $deleted);
    }

    public function getRandomThreeAudiosByEntityId(int $id): array
    {
        $iter = $this->rels->where("entity", $id);
        $ids = [];

        foreach ($iter as $it) {
            $ids[] = $it->audio;
        }

        $shuffleSeed    = openssl_random_pseudo_bytes(6);
        $shuffleSeed    = hexdec(bin2hex($shuffleSeed));

        $ids = knuth_shuffle($ids, $shuffleSeed);
        $ids = array_slice($ids, 0, 3);
        $audios = [];

        foreach ($ids as $id) {
            $audio = $this->get((int) $id);

            if (!$audio || $audio->isDeleted()) {
                continue;
            }

            $audios[] = $audio;
        }

        return $audios;
    }

    public function getByClub(Club $club, int $page = 1, ?int $perPage = null, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($club->getId() * -1, ($perPage * ($page - 1)), $perPage, $deleted);
    }

    public function getPlaylistsByUser(User $user, int $page = 1, ?int $perPage = null, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getPlaylistsByEntityId($user->getId(), ($perPage * ($page - 1)), $perPage, $deleted);
    }

    public function getPlaylistsByClub(Club $club, int $page = 1, ?int $perPage = null, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getPlaylistsByEntityId($club->getId() * -1, ($perPage * ($page - 1)), $perPage, $deleted);
    }

    public function getCollectionSizeByEntityId(int $id): int
    {
        return sizeof($this->rels->where("entity", $id));
    }

    public function getUserCollectionSize(User $user): int
    {
        return sizeof($this->rels->where("entity", $user->getId()));
    }

    public function getClubCollectionSize(Club $club): int
    {
        return sizeof($this->rels->where("entity", $club->getId() * -1));
    }

    public function getUserPlaylistsCount(User $user): int
    {
        return sizeof($this->playlistImports->where("entity", $user->getId()));
    }

    public function getClubPlaylistsCount(Club $club): int
    {
        return sizeof($this->playlistImports->where("entity", $club->getId() * -1));
    }

    public function getByUploader(User $user): EntityStream
    {
        $search = $this->audios->where([
            "owner"   => $user->getId(),
            "deleted" => 0,
        ]);

        return new EntityStream("Audio", $search);
    }

    public function getGlobal(int $order, ?string $genreId = null): EntityStream
    {
        $search = $this->audios->where([
            "deleted"   => 0,
            "unlisted"  => 0,
            "withdrawn" => 0,
        ])->order($order == Audios::ORDER_NEW ? "created DESC" : "listens DESC");

        if (!is_null($genreId)) {
            $search = $search->where("genre", $genreId);
        }

        return new EntityStream("Audio", $search);
    }

    public function search(string $query, int $sortMode = 0, bool $performerOnly = false, bool $withLyrics = false): EntityStream
    {
        $columns = $performerOnly ? "performer" : "performer, name";
        $order   = (["created", "length", "listens"][$sortMode] ?? "") . " DESC";

        $search = $this->audios->where([
            "unlisted" => 0,
            "deleted"  => 0,
        ])->where("MATCH ($columns) AGAINST (? IN BOOLEAN MODE)", "%$query%")->order($order);

        if ($withLyrics) {
            $search = $search->where("lyrics IS NOT NULL");
        }

        return new EntityStream("Audio", $search);
    }

    public function searchPlaylists(string $query): EntityStream
    {
        $search = $this->playlists->where([
            "unlisted" => 0,
            "deleted" => 0,
        ])->where("MATCH (`name`, `description`) AGAINST (? IN BOOLEAN MODE)", $query);

        return new EntityStream("Playlist", $search);
    }

    public function getNew(): EntityStream
    {
        return new EntityStream("Audio", $this->audios->where("created >= " . (time() - 259200))->where(["withdrawn" => 0, "deleted" => 0, "unlisted" => 0])->order("created DESC")->limit(25));
    }

    public function getPopular(): EntityStream
    {
        return new EntityStream("Audio", $this->audios->where("listens > 0")->where(["withdrawn" => 0, "deleted" => 0, "unlisted" => 0])->order("listens DESC")->limit(25));
    }

    public function isAdded(int $user_id, int $audio_id): bool
    {
        return !is_null($this->rels->where([
            "entity" => $user_id,
            "audio"  => $audio_id,
        ])->fetch());
    }

    public function find(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false], int $page = 1, ?int $perPage = null): \Traversable
    {
        $query = "%$query%";
        $result = $this->audios->where([
            "unlisted"  => 0,
            "deleted"   => 0,
            /*"withdrawn" => 0,
            "processed" => 1,*/
        ]);
        $order_str = (in_array($order['type'], ['id', 'length', 'listens']) ? $order['type'] : 'id') . ' ' . ($order['invert'] ? 'ASC' : 'DESC');
        ;

        if ($params["only_performers"] ?? null == "1") {
            $result->where("performer LIKE ?", $query);
        } else {
            $result->where("CONCAT_WS(' ', performer, name) LIKE ?", $query);
        }

        foreach ($params as $paramName => $paramValue) {
            if (is_null($paramValue) || $paramValue == '') {
                continue;
            }

            switch ($paramName) {
                case "before":
                    $result->where("created < ?", $paramValue);
                    break;
                case "after":
                    $result->where("created > ?", $paramValue);
                    break;
                case "with_lyrics":
                    $result->where("lyrics IS NOT NULL");
                    break;
                case 'genre':
                    if ($paramValue == 'any') {
                        break;
                    }

                    $result->where("genre", $paramValue);
                    break;
            }
        }

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Audio", $result);
    }

    public function findPlaylists(string $query, array $params = [], array $order = ['type' => 'id', 'invert' => false]): \Traversable
    {
        $query = "%$query%";
        $result = $this->playlists->where([
            "deleted"  => 0,
        ])->where("CONCAT_WS(' ', name, description) LIKE ?", $query);
        $order_str = (in_array($order['type'], ['id', 'length', 'listens']) ? $order['type'] : 'id') . ' ' . ($order['invert'] ? 'ASC' : 'DESC');

        if (is_null($params['from_me']) || empty($params['from_me'])) {
            $result->where(["unlisted" => 0]);
        }

        foreach ($params as $paramName => $paramValue) {
            if (is_null($paramValue) || $paramValue == '') {
                continue;
            }

            switch ($paramName) {
                # БУДЬ МАКСИМАЛЬНО АККУРАТЕН С ДАННЫМ ПАРАМЕТРОМ
                case "from_me":
                    $result->where("owner", $paramValue);
                    break;
            }
        }

        if ($order_str) {
            $result->order($order_str);
        }

        return new Util\EntityStream("Playlist", $result);
    }
}
