<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Audio;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\Photo;
use openvk\Web\Models\Entities\Playlist;
use openvk\Web\Models\Repositories\Audios;
use openvk\Web\Models\Repositories\Clubs;
use openvk\Web\Models\Repositories\Users;

final class AudioPresenter extends OpenVKPresenter
{
    private $audios;

    const MAX_AUDIO_SIZE = 25000000;

    function __construct(Audios $audios)
    {
        $this->audios = $audios;
    }

    private function renderApp(string $playlistHandle): void
    {
        $this->assertUserLoggedIn();

        $this->template->_template = "Audio/Player";
        $this->template->handle    = $playlistHandle;
    }

    function renderPopular(): void
    {
        $this->renderList(NULL, "popular");
    }

    function renderNew(): void
    {
        $this->renderList(NULL, "new");
    }

    function renderList(?int $owner = NULL, ?string $mode = "list"): void
    {
        $this->template->_template = "Audio/List.xml";
        $page = (int)($this->queryParam("p") ?? 1);
        $audios = [];

        if ($mode === "list") {
            $entity = NULL;
            if ($owner < 0) {
                $entity = (new Clubs)->get($owner * -1);
                if (!$entity || $entity->isBanned())
                    $this->redirect("/audios" . $this->user->id);

                $audios = $this->audios->getByClub($entity, $page, 10);
                $audiosCount = $this->audios->getClubCollectionSize($entity);
            } else {
                $entity = (new Users)->get($owner);
                if (!$entity || $entity->isDeleted() || $entity->isBanned())
                    $this->redirect("/audios" . $this->user->id);

                if(!$entity->getPrivacyPermission("audios.read", $this->user->identity))
                    $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));

                $audios = $this->audios->getByUser($entity, $page, 10);
                $audiosCount = $this->audios->getUserCollectionSize($entity);
            }

            if (!$entity)
                $this->notFound();

            $this->template->owner = $entity;
            $this->template->ownerId = $owner;
            $this->template->isMy = ($owner > 0 && ($entity->getId() === $this->user->id));
            $this->template->isMyClub = ($owner < 0 && $entity->canBeModifiedBy($this->user->identity));
        } else if ($mode === "new") {
            $audios = $this->audios->getNew();
            $audiosCount = $audios->size();
        } else if ($mode === "playlists") {
            if($owner < 0) {
                $entity = (new Clubs)->get(abs($owner));
                if (!$entity || $entity->isBanned())
                    $this->redirect("/playlists" . $this->user->id);

                $playlists = $this->audios->getPlaylistsByClub($entity, $page, 10);
                $playlistsCount = $this->audios->getClubPlaylistsCount($entity);
            } else {
                $entity = (new Users)->get($owner);
                if (!$entity || $entity->isDeleted() || $entity->isBanned())
                    $this->redirect("/playlists" . $this->user->id);

                if(!$entity->getPrivacyPermission("audios.read", $this->user->identity))
                    $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));

                $playlists = $this->audios->getPlaylistsByUser($entity, $page, 10);
                $playlistsCount = $this->audios->getUserPlaylistsCount($entity);
            }

            $this->template->playlists = iterator_to_array($playlists);
            $this->template->playlistsCount = $playlistsCount;
            $this->template->owner = $entity;
            $this->template->ownerId = $owner;
            $this->template->isMy = ($owner > 0 && ($entity->getId() === $this->user->id));
            $this->template->isMyClub = ($owner < 0 && $entity->canBeModifiedBy($this->user->identity));
        } else {
            $audios = $this->audios->getPopular();
            $audiosCount = $audios->size();
        }

        // $this->renderApp("owner=$owner");
        if ($audios !== []) {
            $this->template->audios = iterator_to_array($audios);
            $this->template->audiosCount = $audiosCount;
        }

        $this->template->mode = $mode;
        $this->template->page = $page;
    }

    function renderView(int $owner, int $id): void
    {
        $this->assertUserLoggedIn();

        $audio = $this->audios->getByOwnerAndVID($owner, $id);
        if(!$audio || $audio->isDeleted())
            $this->notFound();

        if(!$audio->canBeViewedBy($this->user->identity))
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            switch ($this->queryParam("act")) {
                case "remove":
                    DatabaseConnection::i()->getContext()->query("DELETE FROM `audio_relations` WHERE `entity` = ? AND `audio` = ?", $this->user->id, $audio->getId());
                    break;

                case "edit":
                    break;

                default:
                    $this->returnJson(["success" => false, "error" => "Action not implemented or not exists"]);
            }
        } else {
            $this->renderApp("id=" . $audio->getId());
        }
    }

    function renderEmbed(int $owner, int $id): void
    {
        $audio = $this->audios->getByOwnerAndVID($owner, $id);
        if(!$audio) {
            header("HTTP/1.1 404 Not Found");
            exit("<b>" . tr("audio_embed_not_found") . ".</b>");
        } else if($audio->isDeleted()) {
            header("HTTP/1.1 410 Not Found");
            exit("<b>" . tr("audio_embed_deleted") . ".</b>");
        } else if($audio->isWithdrawn()) {
            header("HTTP/1.1 451 Unavailable for legal reasons");
            exit("<b>" . tr("audio_embed_withdrawn") . ".</b>");
        } else if(!$audio->canBeViewedBy(NULL)) {
            header("HTTP/1.1 403 Forbidden");
            exit("<b>" . tr("audio_embed_forbidden") . ".</b>");
        } else if(!$audio->isAvailable()) {
            header("HTTP/1.1 425 Too Early");
            exit("<b>" . tr("audio_embed_processing") . ".</b>");
        }

        $this->template->audio = $audio;
    }

    function renderUpload(): void
    {
        $this->assertUserLoggedIn();

        $group = NULL;
        if(!is_null($this->queryParam("gid"))) {
            $gid   = (int) $this->queryParam("gid");
            $group = (new Clubs)->get($gid);
            if(!$group)
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"));

            // TODO check if group allows uploads to anyone
            if(!$group->canBeModifiedBy($this->user->identity))
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"));
        }

        $this->template->group = $group;

        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;

        $upload = $_FILES["blob"];
        if(isset($upload) && file_exists($upload["tmp_name"])) {
            if($upload["size"] > self::MAX_AUDIO_SIZE)
                $this->flashFail("err", tr("error"), tr("media_file_corrupted_or_too_large"));
        } else {
            $err = !isset($upload) ? 65536 : $upload["error"];
            $err = str_pad(dechex($err), 9, "0", STR_PAD_LEFT);
            $this->flashFail("err", tr("error"), tr("error_generic") . "Upload error: 0x$err");
        }

        $performer = $this->postParam("performer");
        $name      = $this->postParam("name");
        $lyrics    = $this->postParam("lyrics");
        $genre     = empty($this->postParam("genre")) ? "undefined" : $this->postParam("genre");
        $nsfw      = ($this->postParam("explicit") ?? "off") === "on";
        if(empty($performer) || empty($name) || iconv_strlen($performer . $name) > 128) # FQN of audio must not be more than 128 chars
            $this->flashFail("err", tr("error"), tr("error_insufficient_info"));

        $audio = new Audio;
        $audio->setOwner($this->user->id);
        $audio->setName($name);
        $audio->setPerformer($performer);
        $audio->setLyrics(empty($lyrics) ? NULL : $lyrics);
        $audio->setGenre($genre);
        $audio->setExplicit($nsfw);

        try {
            $audio->setFile($upload);
        } catch(\DomainException $ex) {
            $e = $ex->getMessage();
            $this->flashFail("err", tr("error"), tr("media_file_corrupted_or_too_large") . " $e.");
        }

        $audio->save();
        $audio->add($group ?? $this->user->identity);

        $this->redirect(is_null($group) ? "/audios" . $this->user->id : "/audios-" . $group->getId());
    }

    function renderListen(int $id): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertUserLoggedIn();
            $this->assertNoCSRF();

            $audio = $this->audios->get($id);

            if ($audio && !$audio->isDeleted() && !$audio->isWithdrawn()) {
                $listen = $audio->listen($this->user->identity);
                $this->returnJson(["success" => $listen]);
            }
        }
    }

    function renderSearch(): void
    {
        $this->redirect("/search?type=audios");
    }

    function renderNewPlaylist(): void
    {
        $owner = $this->user->id;

        if ($this->requestParam("owner")) {
            $club = (new Clubs)->get((int) abs($this->requestParam("owner")));
            if (!$club || $club->isBanned() || !$club->canBeModifiedBy($this->user->identity))
                $this->redirect("/audios" . $this->user->id);

            $owner = ($club->getId() * -1);

            $this->template->club = $club;
        }

        $this->template->owner = $owner;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $title = $this->postParam("title");
            $description = $this->postParam("description");
            $audios = !empty($this->postParam("audios")) ? explode(",", $this->postParam("audios")) : [];

            if(empty($title) || iconv_strlen($title) < 1)
                $this->flash("err", tr("error"), "ну там короч нету имени ну хз");

            $playlist = new Playlist;
            $playlist->setOwner($owner);
            $playlist->setName(substr($title, 0, 128));
            $playlist->setDescription(substr($description, 0, 2048));
            $playlist->save();

            foreach ($audios as $audio) {
                DatabaseConnection::i()->getContext()->query("INSERT INTO `playlist_relations` (`collection`, `media`) VALUES (?, ?)", $playlist->getId(), $audio);
            }

            DatabaseConnection::i()->getContext()->query("INSERT INTO `playlist_imports` (`entity`, `playlist`) VALUES (?, ?)", $owner, $playlist->getId());

            $this->redirect("/playlist" . $owner . "_" . $playlist->getId());
        } else {
            $this->template->audios = iterator_to_array($this->audios->getByUser($this->user->identity, 1, 10));
        }
    }

    function renderPlaylist(int $owner_id, int $virtual_id): void
    {
        $playlist = $this->audios->getPlaylistByOwnerAndVID($owner_id, $virtual_id);
        $page = (int)($this->queryParam("p") ?? 1);
        if (!$playlist || $playlist->isDeleted())
            $this->notFound();

        $this->template->playlist = $playlist;
        $this->template->page = $page;
        $this->template->audios = iterator_to_array($playlist->fetch($page, 10));
        $this->template->isBookmarked = $playlist->isBookmarkedBy($this->user->identity);
        $this->template->isMy = $playlist->getOwner()->getId() === $this->user->id;
        $this->template->canEdit = ($this->template->isMy || ($playlist->getOwner() instanceof Club && $playlist->getOwner()->canBeModifiedBy($this->user->identity)));

        /*if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->template->canEdit) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }

            $title = $this->postParam("title");
            $description = $this->postParam("description");
            $audios = !empty($this->postParam("audios")) ? explode(",", $this->postParam("audios")) : [];

            $playlist->setName(substr($title, 0, 128));
            $playlist->setDescription(substr($description, 0,  2048));
            $playlist->setEdited(time());

            if ($_FILES["cover"]["error"] === UPLOAD_ERR_OK) {
                $photo = new Photo;
                $photo->setOwner($this->user->id);
                $photo->setDescription("Playlist #" . $playlist->getId() . " cover image");
                $photo->setFile($_FILES["cover"]);
                $photo->setCreated(time());
                $photo->save();

                $playlist->setCover_Photo_Id($photo->getId());
            }

            $playlist->save();

            $_ids = [];

            foreach ($playlist->getAudios() as $audio) {
                $_ids[] = $audio->getId();
            }

            foreach ($playlist->getAudios() as $audio) {
                if (!in_array($audio->getId(), $audios)) {
                    DatabaseConnection::i()->getContext()->query("DELETE FROM `playlist_relations` WHERE `collection` = ? AND `media` = ?", $playlist->getId(), $audio->getId());
                }
            }

            foreach ($audios as $audio) {
                if (!in_array($audio, $_ids)) {
                    DatabaseConnection::i()->getContext()->query("INSERT INTO `playlist_relations` (`collection`, `media`) VALUES (?, ?)", $playlist->getId(), $audio);
                }
            }

            $this->flash("succ", tr("changes_saved"));
            $this->redirect("/playlist" . $playlist->getOwner()->getId() . "_" . $playlist->getId());
        }*/
    }

    function renderAction(int $audio_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);
        $this->assertNoCSRF();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit(":)");
        }

        $audio = $this->audios->get($audio_id);

        if(!$audio || $audio->isDeleted())
            $this->flashFail("err", "error", tr("invalid_audio"), null, true);

        switch ($this->queryParam("act")) {
            case "add":
                if($audio->isWithdrawn())
                    $this->flashFail("err", "error", tr("invalid_audio"), null, true);

                if(!$audio->isInLibraryOf($this->user->identity))
                    $audio->add($this->user->identity);
                else
                    $this->flashFail("err", "error", tr("do_have_audio"), null, true);
                
                break;

            case "remove":
                if($audio->isInLibraryOf($this->user->identity))
                    $audio->remove($this->user->identity);
                else
                    $this->flashFail("err", "error", tr("do_not_have_audio"), null, true);

                break;
            case "edit":
                $audio = $this->audios->get($audio_id);
                if (!$audio || $audio->isDeleted() || $audio->isWithdrawn())
                    $this->flashFail("err", "error", tr("invalid_audio"), null, true);

                if ($audio->getOwner()->getId() !== $this->user->id)
                    $this->flashFail("err", "error", tr("access_denied"), null, true);

                $performer = $this->postParam("performer");
                $name      = $this->postParam("name");
                $lyrics    = $this->postParam("lyrics");
                $genre     = empty($this->postParam("genre")) ? "undefined" : $this->postParam("genre");
                $nsfw      = (int)($this->postParam("explicit") ?? 0) === 1;
                $unlisted  = (int)($this->postParam("unlisted") ?? 0) === 1;
                if(empty($performer) || empty($name) || iconv_strlen($performer . $name) > 128) # FQN of audio must not be more than 128 chars
                    $this->flashFail("err", tr("error"), tr("error_insufficient_info"), null, true);

                $audio->setName($name);
                $audio->setPerformer($performer);
                $audio->setLyrics(empty($lyrics) ? NULL : $lyrics);
                $audio->setGenre($genre);
                $audio->setExplicit($nsfw);
                $audio->setSearchability($unlisted);
                $audio->save();

                $this->returnJson(["success" => true, "new_info" => [
                    "name" => ovk_proc_strtr($audio->getTitle(), 40),
                    "performer" => ovk_proc_strtr($audio->getPerformer(), 40),
                    "lyrics" => nl2br($audio->getLyrics() ?? ""),
                    "lyrics_unformatted" => $audio->getLyrics() ?? "",
                    "explicit" => $audio->isExplicit(),
                    "genre" => $audio->getGenre(),
                    "unlisted" => $audio->isUnlisted(),
                ]]);
                break;

            default:
                break;
        }

        $this->returnJson(["success" => true]);
    }

    function renderPlaylists(int $owner)
    {
        $this->renderList($owner, "playlists");
    }

    function renderApiGetContext()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("<select><select><select><select>");
        }

        $ctx_type = $this->postParam("context");
        $ctx_id = (int)($this->postParam("context_entity"));
        $page = (int)($this->postParam("page") ?? 1);
        $perPage = 10;

        switch($ctx_type) {
            default:
            case "entity_audios":
                if($ctx_id >= 0) {
                    $entity = $ctx_id != 0 ? (new Users)->get($ctx_id) : $this->user->identity;

                    if(!$entity || !$entity->getPrivacyPermission("audios.read", $this->user->identity))
                        $this->flashFail("err", "Error", "Can't get queue", 80, true);

                    $audios = $this->audios->getByUser($entity, $page, $perPage);
                    $audiosCount = $this->audios->getUserCollectionSize($entity);
                } else {
                    $entity = (new Clubs)->get(abs($ctx_id));

                    if(!$entity || $entity->isBanned())
                        $this->flashFail("err", "Error", "Can't get queue", 80, true);

                    $audios = $this->audios->getByClub($entity, $page, $perPage);
                    $audiosCount = $this->audios->getClubCollectionSize($entity);
                }
                break;
            case "new_audios":
                $audios = $this->audios->getNew();
                $audiosCount = $audios->size();
                break;
            case "popular_audios":
                $audios = $this->audios->getPopular();
                $audiosCount = $audios->size();
                break;
            case "playlist_context":
                $playlist = $this->audios->getPlaylist($ctx_id);

                if (!$playlist || $playlist->isDeleted())
                    $this->flashFail("err", "Error", "Can't get queue", 80, true);

                $audios = $playlist->fetch($page, 10);
                $audiosCount = $playlist->size();
                break;
            case "search_context":
                $stream = $this->audios->search($this->postParam("query"), 2);
                $audios = $stream->page($page, 10);
                $audiosCount = $stream->size();
                break;
        }

        $pagesCount = ceil($audiosCount / $perPage);

        # костылёк для получения плееров в пикере аудиозаписей
        if((int)($this->postParam("returnPlayers")) === 1) {
            $this->template->audios = $audios;
            $this->template->page = $page;
            $this->template->pagesCount = $pagesCount;
            $this->template->count = $audiosCount;

            return 0;
        }

        $audiosArr = [];

        foreach($audios as $audio) {
            $audiosArr[] = [
                "id" => $audio->getId(),
                "name" => $audio->getTitle(),
                "performer" => $audio->getPerformer(),
                "keys" => $audio->getKeys(),
                "url" => $audio->getUrl(),
                "length" => $audio->getLength(),
                "available" => $audio->isAvailable(),
                "withdrawn" => $audio->isWithdrawn(),
            ];
        }

        $resultArr = [
            "success" => true,
            "page" => $page,
            "perPage" => $perPage,
            "pagesCount" => $pagesCount,
            "count" => $audiosCount,
            "items" => $audiosArr,
        ];

        $this->returnJson($resultArr);
    }
}