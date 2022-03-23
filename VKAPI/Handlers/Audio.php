<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Audio as AEntity;
use openvk\Web\Models\Repositories\Audios;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Util\EntityStream;

final class Audio extends VKAPIRequestHandler
{
    private function toSafeAudioStruct(?AEntity $audio, ?string $hash = NULL, bool $need_user = false): object
    {
        if(!$audio)
            $this->fail(0404, "Audio not found");
        else if(!$audio->canBeViewedBy($this->getUser()))
            $this->fail(201, "Access denied to audio(" . $audio->getPrettyId() . ")");

        # рофлан ебало
        $privApi  = $hash && $GLOBALS["csrfCheck"];
        $audioObj = $audio->toVkApiStruct($this->getUser());
        if(!$privApi) {
            $audioObj->manifest = false;
            $audioObj->keys     = false;
        }

        if($need_user) {
            $user = (new \openvk\Web\Models\Repositories\Users)->get($audio->getOwner()->getId());
            $audioObj->user = (object) [
                "id"       => $user->getId(),
                "photo"    => $user->getAvatarUrl(),
                "name"     => $user->getCanonicalName(),
                "name_gen" => $user->getCanonicalName(),
            ];
        }

        return $audioObj;
    }

    private function streamToResponse(EntityStream $es, int $offset, int $count, ?string $hash = NULL): object
    {
        $items = [];
        foreach($es->offsetLimit($offset, $count) as $audio) {
            $items[] = $this->toSafeAudioStruct($audio, $hash);
        }

        return (object) [
            "count" => sizeof($items),
            "items" => $items,
        ];
    }

    private function validateGenre(?string& $genre_str, ?int $genre_id): void
    {
        if(!is_null($genre_str)) {
            if(!in_array($genre_str, AEntity::genres))
                $this->fail(8, "Invalid genre_str");
        } else if(!is_null($genre_id)) {
            $genre_str = array_flip(AEntity::vkGenres)[$genre_id] ?? NULL;
            if(!$genre_str)
                $this->fail(8, "Invalid genre ID $genre_id");
        }
    }

    function getById(string $audios, ?string $hash = NULL, int $need_user = 0): object
    {
        $this->requireUser();

        $audioIds = array_unique(explode(",", $audios));
        if(sizeof($audioIds) === 1) {
            $descriptor = explode("_", $audioIds[0]);
            if(sizeof($descriptor) === 1) {
                if(ctype_digit($descriptor[0])) {
                    $audio = (new Audios)->get((int) $descriptor[0]);
                } else {
                    $aid = base64_decode($descriptor[0], true);
                    if(!$aid)
                        $this->fail(8, "Invalid audio $audioIds[0]");

                    $audio = (new Audios)->get((int) $aid);
                }
            } else if(sizeof($descriptor) === 2) {
                $audio = (new Audios)->getByOwnerAndVID((int) $descriptor[0], (int) $descriptor[1]);
            } else {
                $this->fail(8, "Invalid audio $audioIds[0]");
            }

            return (object) [
                "count" => 1,
                "items" => [
                    $this->toSafeAudioStruct($audio, $hash, (bool) $need_user),
                ],
            ];
        } else if(sizeof($audioIds) > 6000) {
            $this->fail(1980, "Can't get more than 6000 audios at once");
        }

        $audios = [];
        foreach($audioIds as $id)
            $audios[] = $this->getById($id, $hash)->items[0];

        return (object) [
            "count" => sizeof($audios),
            "items" => $audios,
        ];
    }

    // TODO stub
    function getRecommendations(): object
    {
        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    function getPopular(?int $genre_id = NULL, ?string $genre_str = NULL, int $offset = 0, int $count = 100, ?string $hash = NULL): object
    {
        $this->requireUser();
        $this->validateGenre($genre_str, $genre_id);

        $results = (new Audios)->getGlobal(Audios::ORDER_POPULAR, $genre_str);

        return $this->streamToResponse($results, $offset, $count, $hash);
    }

    function getFeed(?int $genre_id = NULL, ?string $genre_str = NULL, int $offset = 0, int $count = 100, ?string $hash = NULL): object
    {
        $this->requireUser();
        $this->validateGenre($genre_str, $genre_id);

        $results = (new Audios)->getGlobal(Audios::ORDER_NEW, $genre_str);

        return $this->streamToResponse($results, $offset, $count, $hash);
    }

    function search(string $q, int $auto_complete = 0, int $lyrics = 0, int $performer_only = 0, int $sort = 2, int $search_own = 0, int $offset = 0, int $count = 30, ?string $hash = NULL): object
    {
        $this->requireUser();

        if(($auto_complete + $search_own) != 0)
            $this->fail(10, "auto_complete and search_own are not supported");
        else if($count > 300 || $count < 1)
            $this->fail(8, "count is invalid: $count");

        $results = (new Audios)->search($q, $sort, (bool) $performer_only, (bool) $lyrics);

        return $this->streamToResponse($results, $offset, $count, $hash);
    }

    function getCount(int $owner_id, int $uploaded_only = 0): int
    {
        if($owner_id < 0) {
            $owner_id *= -1;
            $group = (new Clubs)->get($owner_id);
            if(!$group)
                $this->fail(0404, "Group not found");

            return (new Audios)->getClubCollectionSize($group);
        }

        $user = (new \openvk\Web\Models\Repositories\Users)->get($owner_id);
        if(!$user)
            $this->fail(0404, "User not found");

        if($uploaded_only) {
            return DatabaseConnection::i()->getContext()->table("audios")
                ->where([
                    "deleted" => false,
                    "owner" => $owner_id,
                ])->count();
        }

        return (new Audios)->getUserCollectionSize($user);
    }

	function get(int $owner_id = 0, int $album_id = 0, ?string $audio_ids = NULL, int $need_user = 1, int $offset = 0, int $count = 100, int $uploaded_only = 0, int $need_seed = 0, ?string $shuffle_seed = NULL, int $shuffle = 0, ?string $hash = NULL): object
	{
		$this->requireUser();

        if($album_id != 0)
            $this->fail(10, "album_id is not supported");

        $shuffleSeed    = NULL;
        $shuffleSeedStr = NULL;
        if($shuffle == 1) {
            if(!$shuffle_seed) {
                if($need_seed == 1) {
                    $shuffleSeed    = openssl_random_pseudo_bytes(6);
                    $shuffleSeedStr = base64_encode($shuffleSeed);
                    $shuffleSeed    = hexdec(bin2hex($shuffleSeed));
                } else {
                    $hOffset        = ((int) date("i") * 60) + (int) date("s");
                    $thisHour       = time() - $hOffset;
                    $shuffleSeed    = $thisHour + $this->getUser()->getId();
                    $shuffleSeedStr = base64_encode(hex2bin(dechex($shuffleSeed)));
                }
            } else {
                $shuffleSeed    = hexdec(bin2hex(base64_decode($shuffle_seed)));
                $shuffleSeedStr = $shuffle_seed;
            }
        }

        if(!is_null($audio_ids)) {
            $audio_ids = explode(",", $audio_ids);
            if(!$audio_ids)
                $this->fail(10, "Audio::get@L0d186:explode(string): Unknown error");

            if(!is_null($shuffleSeed))
                $audio_ids = knuth_shuffle($audio_ids, $shuffleSeed);

            $obj = $this->getById(implode(",", $audio_ids), $hash, $need_user);
            if(!is_null($shuffleSeed))
                $obj->shuffle_seed = $shuffleSeedStr;

            return $obj;
        }

        $dbCtx = DatabaseConnection::i()->getContext();
        if($uploaded_only == 1) {
            if($owner_id <= 0)
                $this->fail(8, "uploaded_only can only be used with owner_id > 0");

            if(!is_null($shuffleSeed)) {
                $audio_ids = [];
                $query     = $dbCtx->table("audios")->select("virtual_id")->where([
                    "owner"   => $owner_id,
                    "deleted" => 0,
                ]);

                foreach($query as $res)
                    $audio_ids[] = $res->virtual_id;

                $audio_ids = knuth_shuffle($audio_ids, $shuffleSeed);
                $audio_ids = array_slice($audio_ids, $offset, $count);
                $audio_q   = ""; # audio.getById query
                foreach($audio_ids as $aid)
                    $audio_q .= ",$owner_id" . "_$aid";

                $obj = $this->getById(substr($audio_q, 1), $hash, $need_user);
                $obj->shuffle_seed = $shuffleSeedStr;

                return $obj;
            }

            $res = (new Audios)->getByUploader((new \openvk\Web\Models\Repositories\Users)->get($owner_id));

            return $this->streamToResponse($res, $offset, $count, $hash, $need_user);
        }

        $query = $dbCtx->table("audio_relations")->select("audio")->where("entity", $owner_id);
        if(!is_null($shuffleSeed)) {
            $audio_ids = [];
            foreach($query as $aid)
                $audio_ids[] = $aid->audio;

            $audio_ids = knuth_shuffle($audio_ids, $shuffleSeed);
            $audio_ids = array_slice($audio_ids, $offset, $count);
            $audio_q   = "";
            foreach($audio_ids as $aid)
                $audio_q .= ",$aid";

            $obj = $this->getById(substr($audio_q, 1), $hash, $need_user);
            $obj->shuffle_seed = $shuffleSeedStr;

            return $obj;
        }

        $items  = [];
        $audios = (new Audios)->getByEntityID($owner_id, $offset, $count);
        foreach($audios as $audio)
            $items[] = $this->toSafeAudioStruct($audio, $hash, $need_user == 1);

        return (object) [
            "count" => sizeof($items),
            "items" => $items,
        ];
	}

    function getLyrics(int $lyrics_id): object
    {
        $this->requireUser();

        $audio = (new Audios)->get($lyrics_id);
        if(!$audio || !$audio->getLyrics())
            $this->fail(0404, "Not found");

        if(!$audio->canBeViewedBy($this->getUser()))
            $this->fail(201, "Access denied to lyrics");

        return (object) [
            "lyrics_id" => $lyrics_id,
            "text"      => preg_replace("%\r\n?%", "\n", $audio->getLyrics()),
        ];
    }

    function beacon(int $aid, ?int $gid = NULL): int
    {
        $this->requireUser();

        $audio = (new Audios)->get($aid);
        if(!$audio)
            $this->fail(0404, "Not Found");
        else if(!$audio->canBeViewedBy($this->getUser()))
            $this->fail(201, "Insufficient permissions to listen this audio");

        $group = NULL;
        if(!is_null($group)) {
            $group = (new Clubs)->get($gid);
            if(!$group)
                $this->fail(0404, "Not Found");
            else if(!$group->canBeModifiedBy($this->getUser()))
                $this->fail(203, "Insufficient rights to this group");
        }

        return (int) $audio->listen($group ?? $this->getUser());
    }

    function setBroadcast(string $audio, string $target_ids): array
    {
        $this->requireUser();

        [$owner, $aid] = explode("_", $audio);
        $song = (new Audios)->getByOwnerAndVID($owner, $aid);
        $ids  = [];
        foreach(explode(",", $target_ids) as $id) {
            $id = (int) $id;
            if($id > 0) {
                if ($id != $this->getUser()->getId()) {
                    $this->fail(600, "Can't listen on behalf of $id");
                } else {
                    $ids[] = $id;
                    $this->beacon($song->getId());
                    continue;
                }
            }

            $group = (new Clubs)->get($id * -1);
            if(!$group)
                $this->fail(0404, "Not Found");
            else if(!$group->canBeModifiedBy($this->getUser()))
                $this->fail(203, "Insufficient rights to this group");

            $ids[] = $id;
            $this->beacon($song->getId(), $id * -1);
        }

        return $ids;
    }

    function getBroadcastList(string $filter = "all", int $active = 0, ?string $hash = NULL): object
    {
        $this->requireUser();

        if(!in_array($filter, ["all", "friends", "groups"]))
            $this->fail(8, "Invalid filter $filter");

        $dbContext = DatabaseConnection::i()->getContext();
        $entityIds = [];
        $query     = $dbContext->table("subscriptions")->select("model, target")
            ->where("follower", $this->getUser()->getId());

        if($filter != "all")
            $query = $query->where("model = ?", "openvk\\Web\\Models\\Entities\\" . ($filter == "groups" ? "Club" : "User"));

        foreach($query as $_rel) {
            $id = $_rel->target;
            if($_rel->model === "openvk\\Web\\Models\\Entities\\Club")
                $id *= -1;

            $entityIds[] = $id;
        }

        $audioIds  = [];
        $threshold = $active === 0 ? 3600 : 120;
        foreach($entityIds as $ent) {
            $lastListen = $dbContext->table("audio_listens")->where("entity", $ent)
                ->where("time >= ?", time() - $threshold)->fetch();
            if(!$lastListen)
                continue;

            $audio          = (new Audios)->get($lastListen->audio);
            $audioIds[$ent] = $this->toSafeAudioStruct($audio, $hash);
        }

        $items = [];
        foreach($audioIds as $ent => $audio) {
            $entity = ($ent < 0 ? (new Groups($this->getUser())) : (new Users($this->getUser())))
                ->get((string) abs($ent));

            $entity->status_audio = $audio;
        }

        return (object) [
            "count" => sizeof($items),
            "items" => $items,
        ];
    }

    function edit(int $owner_id, int $audio_id, ?string $artist = NULL, ?string $title = NULL, ?string $text = NULL, ?int $genre_id = NULL, ?string $genre_str = NULL, int $no_search = 0): int
    {
        $this->requireUser();

        $audio = (new Audios)->getByOwnerAndVID($owner_id, $audio_id);
        if(!$audio)
            $this->fail(0404, "Not Found");
        else if(!$audio->canBeModifiedBy($this->getUser()))
            $this->fail(201, "Insufficient permissions to edit this audio");

        if(!is_null($genre_id)) {
            $genre = array_flip(AEntity::vkGenres)[$genre_id] ?? NULL;
            if(!$genre)
                $this->fail(8, "Invalid genre ID $genre_id");

            $audio->setGenre($genre);
        } else if(!is_null($genre_str)) {
            if(!in_array($genre_str, AEntity::genres))
                $this->fail(8, "Invalid genre ID $genre_str");

            $audio->setGenre($genre_str);
        }

        $lyrics = 0;
        if(!is_null($text)) {
            $audio->setLyrics($text);
            $lyrics = $audio->getId();
        }

        if(!is_null($artist))
            $audio->setPerformer($artist);

        if(!is_null($title))
            $audio->setName($title);

        $audio->setSearchability(!((bool) $no_search));
        $audio->save();

        return $lyrics;
    }

    function add(int $audio_id, int $owner_id, ?int $group_id = NULL, ?int $album_id = NULL): string
    {
        $this->requireUser();

        if(!is_null($album_id))
            $this->fail(10, "album_id not implemented");

        // TODO get rid of dups
        $to = $this->getUser();
        if(!is_null($group_id)) {
            $group = (new Clubs)->get($group_id);
            if(!$group)
                $this->fail(0404, "Invalid group_id");
            else if(!$group->canBeModifiedBy($this->getUser()))
                $this->fail(203, "Insufficient rights to this group");

            $to = $group;
        }

        $audio = (new Audios)->getByOwnerAndVID($owner_id, $audio_id);
        if(!$audio)
            $this->fail(0404, "Not found");
        else if(!$audio->canBeViewedBy($this->getUser()))
            $this->fail(201, "Access denied to audio(owner=$owner_id, vid=$audio_id)");

        $audio->add($to);

        return $audio->getPrettyId();
    }

    function delete(int $audio_id, int $owner_id, ?int $group_id = NULL): int
    {
        $this->requireUser();

        $from = $this->getUser();
        if(!is_null($group_id)) {
            $group = (new Clubs)->get($group_id);
            if(!$group)
                $this->fail(0404, "Invalid group_id");
            else if(!$group->canBeModifiedBy($this->getUser()))
                $this->fail(203, "Insufficient rights to this group");

            $from = $group;
        }

        $audio = (new Audios)->getByOwnerAndVID($owner_id, $audio_id);
        if(!$audio)
            $this->fail(0404, "Not found");

        $audio->remove($from);

        return 1;
    }

    function restore(int $audio_id, int $owner_id, ?int $group_id = NULL, ?string $hash = NULL): object
    {
        $this->requireUser();

        $vid = $this->add($audio_id, $owner_id, $group_id);

        return $this->getById($vid, $hash)->items[0];
    }
}
