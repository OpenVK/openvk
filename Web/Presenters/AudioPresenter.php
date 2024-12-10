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
    protected $presenterName = "audios";

    const MAX_AUDIO_SIZE = 25000000;

    function __construct(Audios $audios)
    {
        $this->audios = $audios;
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
        $this->assertUserLoggedIn();
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
            $this->template->club = $owner < 0 ? $entity : NULL;
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

                $playlists = $this->audios->getPlaylistsByClub($entity, $page, OPENVK_DEFAULT_PER_PAGE);
                $playlistsCount = $this->audios->getClubPlaylistsCount($entity);
            } else {
                $entity = (new Users)->get($owner);
                if (!$entity || $entity->isDeleted() || $entity->isBanned())
                    $this->redirect("/playlists" . $this->user->id);

                if(!$entity->getPrivacyPermission("audios.read", $this->user->identity))
                    $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));

                $playlists = $this->audios->getPlaylistsByUser($entity, $page, OPENVK_DEFAULT_PER_PAGE);
                $playlistsCount = $this->audios->getUserPlaylistsCount($entity);
            }

            $this->template->playlists = iterator_to_array($playlists);
            $this->template->playlistsCount = $playlistsCount;
            $this->template->owner = $entity;
            $this->template->ownerId = $owner;
            $this->template->club = $owner < 0 ? $entity : NULL;
            $this->template->isMy = ($owner > 0 && ($entity->getId() === $this->user->id));
            $this->template->isMyClub = ($owner < 0 && $entity->canBeModifiedBy($this->user->identity));
        } else if ($mode === 'alone_audio') {
            $audios = [$this->template->alone_audio];
            $audiosCount = 1;

            $this->template->owner   = $this->user->identity;
            $this->template->ownerId = $this->user->id;
        }

        // $this->renderApp("owner=$owner");
        if ($audios !== []) {
            $this->template->audios = iterator_to_array($audios);
            $this->template->audiosCount = $audiosCount;
        }

        $this->template->mode = $mode;
        $this->template->page = $page;
        
        if(in_array($mode, ["list", "new", "popular"]) && $this->user->identity && $page < 2)
            $this->template->friendsAudios = $this->user->identity->getBroadcastList("all", true);
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
        $playlist = NULL;
        $isAjax = $this->postParam("ajax", false) == 1;

        if(!is_null($this->queryParam("gid")) && !is_null($this->queryParam("playlist"))) {
            $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
        }

        if(!is_null($this->queryParam("gid"))) {
            $gid   = (int) $this->queryParam("gid");
            $group = (new Clubs)->get($gid);
            if(!$group)
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);

            if(!$group->canUploadAudio($this->user->identity))
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
        }

        if(!is_null($this->queryParam("playlist"))) {
            $playlist_id = (int)$this->queryParam("playlist");
            $playlist = (new Audios)->getPlaylist($playlist_id);
            if(!$playlist || $playlist->isDeleted())
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);

            if(!$playlist->canBeModifiedBy($this->user->identity))
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
        
            $this->template->playlist = $playlist;
            $this->template->owner = $playlist->getOwner();
        }

        $this->template->group = $group;

        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;

        $upload = $_FILES["blob"];
        if(isset($upload) && file_exists($upload["tmp_name"])) {
            if($upload["size"] > self::MAX_AUDIO_SIZE)
                $this->flashFail("err", tr("error"), tr("media_file_corrupted_or_too_large"), null, $isAjax);
        } else {
            $err = !isset($upload) ? 65536 : $upload["error"];
            $err = str_pad(dechex($err), 9, "0", STR_PAD_LEFT);
            $readableError = tr("error_generic");

            switch($upload["error"]) {
                default:
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $readableError = tr("file_too_big");
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $readableError = tr("file_loaded_partially");
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $readableError = tr("file_not_uploaded");
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $readableError = "Missing a temporary folder.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    $readableError = "Failed to write file to disk. ";
                    break;
            }

            $this->flashFail("err", tr("error"), $readableError . " " . tr("error_code", $err), null, $isAjax);
        }

        $performer = $this->postParam("performer");
        $name      = $this->postParam("name");
        $lyrics    = $this->postParam("lyrics");
        $genre     = empty($this->postParam("genre")) ? "Other" : $this->postParam("genre");
        $nsfw      = ($this->postParam("explicit") ?? "off") === "on";
        $is_unlisted = ($this->postParam("unlisted") ?? "off") === "on";

        if(empty($performer) || empty($name) || iconv_strlen($performer . $name) > 128) # FQN of audio must not be more than 128 chars
            $this->flashFail("err", tr("error"), tr("error_insufficient_info"), null, $isAjax);

        $audio = new Audio;
        $audio->setOwner($this->user->id);
        $audio->setName($name);
        $audio->setPerformer($performer);
        $audio->setLyrics(empty($lyrics) ? NULL : $lyrics);
        $audio->setGenre($genre);
        $audio->setExplicit($nsfw);
        $audio->setUnlisted($is_unlisted);

        try {
            $audio->setFile($upload);
        } catch(\DomainException $ex) {
            $e = $ex->getMessage();
            $this->flashFail("err", tr("error"), tr("media_file_corrupted_or_too_large") . " $e.", null, $isAjax);
        } catch(\RuntimeException $ex) {
            $this->flashFail("err", tr("error"), tr("ffmpeg_timeout"), null, $isAjax);
        } catch(\BadMethodCallException $ex) {
            $this->flashFail("err", tr("error"), "хз", null, $isAjax);
        } catch(\Exception $ex) {
            $this->flashFail("err", tr("error"), tr("ffmpeg_not_installed"), null, $isAjax);
        }

        $audio->save();

        if($playlist) {
            $playlist->add($audio);
        } else {
            $audio->add($group ?? $this->user->identity);
        }

        if(!$isAjax)
            $this->redirect(is_null($group) ? "/audios" . $this->user->id : "/audios-" . $group->getId());
        else {
            $redirectLink = "/audios";

            if(!is_null($group))
                $redirectLink .= $group->getRealId();
            else
                $redirectLink .= $this->user->id;

            if($playlist)
                $redirectLink = "/playlist" . $playlist->getPrettyId();
            
            $this->returnJson([
                "success" => true,
                "redirect_link" => $redirectLink,
            ]);
        }
    }

    function renderAloneAudio(int $owner_id, int $audio_id): void
    {
        $this->assertUserLoggedIn();

        $found_audio = $this->audios->get($audio_id);
        if(!$found_audio || $found_audio->isDeleted() || !$found_audio->canBeViewedBy($this->user->identity)) {
            $this->notFound();
        }

        $this->template->alone_audio = $found_audio;
        $this->renderList(NULL, 'alone_audio');
    }

    function renderListen(int $id): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            if(is_null($this->user))
                $this->returnJson(["success" => false]);

            $audio = $this->audios->get($id);

            if ($audio && !$audio->isDeleted() && !$audio->isWithdrawn()) {
                if(!empty($this->postParam("playlist"))) {
                    $playlist = (new Audios)->getPlaylist((int)$this->postParam("playlist"));

                    if(!$playlist || $playlist->isDeleted() || !$playlist->canBeViewedBy($this->user->identity) || !$playlist->hasAudio($audio))
                        $playlist = NULL;
                }

                $listen = $audio->listen($this->user->identity, $playlist);

                $returnArr = ["success" => $listen];
                
                if($playlist)
                    $returnArr["new_playlists_listens"] = $playlist->getListens();

                $this->returnJson($returnArr);
            }

            $this->returnJson(["success" => false]);
        } else {
            $this->redirect("/");
        }
    }

    function renderSearch(): void
    {
        $this->redirect("/search?section=audios");
    }

    function renderNewPlaylist(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);

        $owner = $this->user->id;

        if ($this->requestParam("gid")) {
            $club = (new Clubs)->get((int) abs((int)$this->requestParam("gid")));
            if (!$club || $club->isBanned() || !$club->canBeModifiedBy($this->user->identity))
                $this->redirect("/audios" . $this->user->id);

            $owner = ($club->getId() * -1);

            $this->template->club = $club;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $title = $this->postParam("title");
            $description = $this->postParam("description");
            $is_unlisted = (int)$this->postParam('is_unlisted');
            $is_ajax = (int)$this->postParam('ajax') == 1;
            $audios = array_slice(explode(",", $this->postParam("audios")), 0, 1000);

            if(empty($title) || iconv_strlen($title) < 1)
                $this->flashFail("err", tr("error"), tr("set_playlist_name"), NULL, $is_ajax);

            $playlist = new Playlist;
            $playlist->setOwner($owner);
            $playlist->setName(substr($title, 0, 125));
            $playlist->setDescription(substr($description, 0, 2045));
            if($is_unlisted == 1)
                $playlist->setUnlisted(true);
            
            if($_FILES["cover"]["error"] === UPLOAD_ERR_OK) {
                if(!str_starts_with($_FILES["cover"]["type"], "image"))
                    $this->flashFail("err", tr("error"), tr("not_a_photo"), NULL, $is_ajax);

                try {
                    $playlist->fastMakeCover($this->user->id, $_FILES["cover"]);
                } catch(\Throwable $e) {
                    $this->flashFail("err", tr("error"), tr("invalid_cover_photo"), NULL, $is_ajax);
                }
            }

            $playlist->save();

            foreach($audios as $audio) {
                $audio = $this->audios->get((int)$audio);
                if(!$audio || $audio->isDeleted())
                    continue;

                $playlist->add($audio);
            }

            $playlist->bookmark(isset($club) ? $club : $this->user->identity);
            if($is_ajax) {
                $this->returnJson([
                    'success' => true,
                    'redirect' => '/playlist' . $owner . "_" . $playlist->getId()
                ]);
            }
            $this->redirect("/playlist" . $owner . "_" . $playlist->getId());
        } else {
            $this->template->owner = $owner;
        }
    }

    function renderPlaylistAction(int $id) {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);
        $this->assertNoCSRF();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            $this->redirect("/");
        }

        $playlist = $this->audios->getPlaylist($id);

        if(!$playlist || $playlist->isDeleted())
            $this->flashFail("err", "error", tr("invalid_playlist"), null, true);

        switch ($this->queryParam("act")) {
            case "bookmark":
                if(!$playlist->isBookmarkedBy($this->user->identity))
                    $playlist->bookmark($this->user->identity);
                else
                    $this->flashFail("err", "error", tr("playlist_already_bookmarked"), null, true);

                break;
            case "unbookmark":
                if($playlist->isBookmarkedBy($this->user->identity))
                    $playlist->unbookmark($this->user->identity);
                else
                    $this->flashFail("err", "error", tr("playlist_not_bookmarked"), null, true);

                break;
            case "delete":
                if($playlist->canBeModifiedBy($this->user->identity)) {
                    $tmOwner = $playlist->getOwner();
                    $playlist->delete();
                } else
                    $this->flashFail("err", "error", tr("access_denied"), null, true);
                
                $this->returnJson(["success" => true, "id" => $tmOwner->getRealId()]);
                break;
            default:
                break;
        }

        $this->returnJson(["success" => true]);
    }

    function renderEditPlaylist(int $owner_id, int $virtual_id)
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $playlist = $this->audios->getPlaylistByOwnerAndVID($owner_id, $virtual_id);
        if (!$playlist || $playlist->isDeleted() || !$playlist->canBeModifiedBy($this->user->identity))
            $this->notFound();

        $this->template->playlist = $playlist;
        
        $audios = iterator_to_array($playlist->fetch(1, $playlist->size()));
        $this->template->audios = array_slice($audios, 0, 1000);
        $this->template->ownerId = $owner_id;
        $this->template->owner = $playlist->getOwner();

        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;

        $is_ajax = (int)$this->postParam('ajax') == 1;
        $title = $this->postParam("title");
        $description = $this->postParam("description");
        $is_unlisted = (int)$this->postParam('is_unlisted');
        $new_audios = !empty($this->postParam("audios")) ? explode(",", rtrim($this->postParam("audios"), ",")) : [];

        if(empty($title) || iconv_strlen($title) < 1)
            $this->flashFail("err", tr("error"), tr("set_playlist_name"));
        
        $playlist->setName(ovk_proc_strtr($title, 125));
        $playlist->setDescription(ovk_proc_strtr($description, 2045));
        $playlist->setEdited(time());
        $playlist->resetLength();
        $playlist->setUnlisted((bool)$is_unlisted);

        if($_FILES["cover"]["error"] === UPLOAD_ERR_OK) {
            if(!str_starts_with($_FILES["cover"]["type"], "image"))
                $this->flashFail("err", tr("error"), tr("not_a_photo"));
            
            try {
                $playlist->fastMakeCover($this->user->id, $_FILES["cover"]);
            } catch(\Throwable $e) {
                $this->flashFail("err", tr("error"), tr("invalid_cover_photo"));
            }
        }

        $playlist->save();
        
        DatabaseConnection::i()->getContext()->table("playlist_relations")->where([
            "collection" => $playlist->getId()
        ])->delete();

        foreach ($new_audios as $new_audio) {
            $audio = (new Audios)->get((int)$new_audio);
            if(!$audio || $audio->isDeleted())
                continue;

            $playlist->add($audio);
        }

        if($is_ajax) {
            $this->returnJson([
                'success' => true,
                'redirect' => '/playlist' . $playlist->getPrettyId()
            ]);
        }
        $this->redirect("/playlist".$playlist->getPrettyId());
    }

    function renderPlaylist(int $owner_id, int $virtual_id): void
    {
        $this->assertUserLoggedIn();
        $playlist = $this->audios->getPlaylistByOwnerAndVID($owner_id, $virtual_id);
        $page = (int)($this->queryParam("p") ?? 1);
        if (!$playlist || $playlist->isDeleted())
            $this->notFound();

        $this->template->playlist = $playlist;
        $this->template->page = $page;
        $this->template->cover = $playlist->getCoverPhoto();
        $this->template->cover_url = $this->template->cover ? $this->template->cover->getURL() : "/assets/packages/static/openvk/img/song.jpg";
        $this->template->audios = iterator_to_array($playlist->fetch($page, 10));
        $this->template->ownerId = $owner_id;
        $this->template->owner = $playlist->getOwner();
        $this->template->isBookmarked = $this->user->identity && $playlist->isBookmarkedBy($this->user->identity);
        $this->template->isMy = $this->user->identity &&  $playlist->getOwner()->getId() === $this->user->id;
        $this->template->canEdit = $this->user->identity && $playlist->canBeModifiedBy($this->user->identity);
        $this->template->count = $playlist->size();
    }

    function renderAction(int $audio_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction(true);
        $this->assertNoCSRF();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            $this->redirect("/");
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
            case "remove_club":
                $club = (new Clubs)->get((int)$this->postParam("club"));
                
                if(!$club || !$club->canBeModifiedBy($this->user->identity))
                    $this->flashFail("err", "error", tr("access_denied"), null, true);
                
                if($audio->isInLibraryOf($club))
                    $audio->remove($club);
                else
                    $this->flashFail("err", "error", tr("group_hasnt_audio"), null, true);

                break;
            case "add_to_club":
                $detailed = [];
                if($audio->isWithdrawn())
                    $this->flashFail("err", "error", tr("invalid_audio"), null, true);
                
                if(empty($this->postParam("clubs")))
                    $this->flashFail("err", "error", 'clubs not passed', null, true);
                
                $clubs_arr = explode(',', $this->postParam("clubs"));
                $count     = sizeof($clubs_arr);
                if($count < 1 || $count > 10) {
                    $this->flashFail("err", "error", tr('too_many_or_to_lack'), null, true);
                }

                foreach($clubs_arr as $club_id) {
                    $club = (new Clubs)->get((int)$club_id);
                    if(!$club || !$club->canBeModifiedBy($this->user->identity))
                        continue;
                        
                    if(!$audio->isInLibraryOf($club)) {
                        $detailed[$club_id] = true;
                        $audio->add($club);
                    } else {
                        $detailed[$club_id] = false;
                        continue;
                    }
                }
                
                $this->returnJson(["success" => true, 'detailed' => $detailed]);
                break;
            case "add_to_playlist":
                $detailed = [];
                if($audio->isWithdrawn())
                    $this->flashFail("err", "error", tr("invalid_audio"), null, true);
                
                if(empty($this->postParam("playlists")))
                    $this->flashFail("err", "error", 'playlists not passed', null, true);
                
                $playlists_arr = explode(',', $this->postParam("playlists"));
                $count = sizeof($playlists_arr);
                if($count < 1 || $count > 10) {
                    $this->flashFail("err", "error", tr('too_many_or_to_lack'), null, true);
                }

                foreach($playlists_arr as $playlist_id) {
                    $pid = explode('_', $playlist_id);
                    $playlist = (new Audios)->getPlaylistByOwnerAndVID((int)$pid[0], (int)$pid[1]);
                    if(!$playlist || !$playlist->canBeModifiedBy($this->user->identity))
                        continue;
                        
                    if(!$playlist->hasAudio($audio)) {
                        $playlist->add($audio);
                        $detailed[$playlist_id] = true;
                    } else {
                        $detailed[$playlist_id] = false;
                        continue;
                    }
                }

                $this->returnJson(["success" => true, 'detailed' => $detailed]);
                break;
            case "delete":
                if($audio->canBeModifiedBy($this->user->identity))
                    $audio->delete();
                else
                    $this->flashFail("err", "error", tr("access_denied"), null, true);

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
                $audio->setEdited(time());
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
            $this->redirect("/");
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
                $stream = $this->audios->search($this->postParam("query"), 2, $this->postParam("type") === "by_performer");
                $audios = $stream->page($page, 10);
                $audiosCount = $stream->size();
                break;
            case "classic_search_context":
                $data = json_decode($this->postParam("context_entity"), true);

                $params = [];
                $order = [
                    "type" => $data['order'] ?? 'id',
                    "invert" => (int)$data['invert'] == 1 ? true : false
                ];

                if($data['genre'] && $data['genre'] != 'any')
                    $params['genre'] = $data['genre'];

                if($data['only_performers'] && (int)$data['only_performers'] == 1)
                    $params['only_performers'] = '1';
            
                if($data['with_lyrics'] && (int)$data['with_lyrics'] == 1)
                    $params['with_lyrics'] = '1';

                $stream = $this->audios->find($data['query'], $params, $order);
                $audios = $stream->page($page, 10);
                $audiosCount = $stream->size();
                break;
            case 'alone_audio':
                $found_audio = $this->audios->get($ctx_id);
                if(!$found_audio || $found_audio->isDeleted() || !$found_audio->canBeViewedBy($this->user->identity)) {
                    $this->flashFail("err", "Error", "Not found", 89, true);
                }

                $audios = [$found_audio];
                $audiosCount = 1;
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
            $output_array = [];
            $output_array['id'] = $audio->getId();
            $output_array['name'] = $audio->getTitle();
            $output_array['performer'] = $audio->getPerformer();

            if(!$audio->isWithdrawn()) {
                $output_array['keys'] = $audio->getKeys();
                $output_array['url'] = $audio->getUrl();
            }

            $output_array['length'] = $audio->getLength();
            $output_array['available'] = $audio->isAvailable();
            $output_array['withdrawn'] = $audio->isWithdrawn();

            $audiosArr[] = $output_array;
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
