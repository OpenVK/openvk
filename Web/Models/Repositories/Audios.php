<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Audio;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Util\EntityStream;

class Audios
{
    private $context;
    private $audios;
    private $rels;

    const ORDER_NEW     = 0;
    const ORDER_POPULAR = 1;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->audios  = $this->context->table("audios");
        $this->rels    = $this->context->table("audio_relations");
    }

    function get(int $id): ?Audio
    {
        $audio = $this->audios->get($id);
        if(!$audio)
            return NULL;

        return new Audio($audio);
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

    private function getByEntityID(int $entity, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $iter = $this->rels->where("entity", $entity)->page($page, $perPage);
        foreach($iter as $rel) {
            $audio = $this->get($rel->audio);
            if(!$audio || $audio->isDeleted()) {
                $deleted++;
                continue;
            }

            yield $audio;
        }
    }

    function getByUser(User $user, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($user->getId(), $page, $perPage, $deleted);
    }

    function getByClub(Club $club, int $page = 1, ?int $perPage = NULL, ?int& $deleted = nullptr): \Traversable
    {
        return $this->getByEntityID($club->getId() * -1, $page, $perPage, $deleted);
    }

    function getUserCollectionSize(User $user): int
    {
        return sizeof($this->rels->where("entity", $user->getId()));
    }

    function getClubCollectionSize(Club $club): int
    {
        return sizeof($this->rels->where("entity", $club->getId() * -1));
    }

    function getByUploader(User $user): EntityStream
    {
        $search = $this->audios->where([
            "owner"   => $user->getId(),
            "deleted" => 0,
        ]);

        return new EntityStream("Audio", $search);
    }

    function getGlobal(int $order): EntityStream
    {
        $search = $this->audios->where([
            "deleted"   => 0,
            "unlisted"  => 0,
            "withdrawn" => 0,
        ])->order($order == Audios::ORDER_NEW ? "created DESC" : "listens DESC");

        return new EntityStream("Audio", $search);
    }

    function search(string $query): EntityStream
    {
        $search = $this->audios->where([
            "unlisted" => 0,
            "deleted"  => 0,
        ])->where("MATCH (performer, name) AGAINST (? WITH QUERY EXPANSION)", $query);

        return new EntityStream("Audio", $search);
    }
}