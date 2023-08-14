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
        $this->template->_template = "Audio/List";

        $audios = [];
        $playlists = [];

        if ($mode === "list") {
            $entity = NULL;
            if ($owner < 0) {
                $entity = (new Clubs)->get($owner * -1);
                if (!$entity || $entity->isBanned())
                    $this->redirect("/audios" . $this->user->id);

                $audios = $this->audios->getByClub($entity);
                $playlists = $this->audios->getPlaylistsByClub($entity);
            } else {
                $entity = (new Users)->get($owner);
                if (!$entity || $entity->isDeleted() || $entity->isBanned())
                    $this->redirect("/audios" . $this->user->id);

                $audios = $this->audios->getByUser($entity);
                $playlists = $this->audios->getPlaylistsByUser($entity);
            }

            if (!$entity)
                $this->notFound();

            $this->template->owner = $entity;
            $this->template->isMy = ($owner > 0 && ($entity->getId() === $this->user->id));
            $this->template->isMyClub = ($owner < 0 && $entity->canBeModifiedBy($this->user->identity));
        } else if ($mode === "new") {
            $audios = $this->audios->getNew();
        } else {
            $audios = $this->audios->getPopular();
        }

        // $this->renderApp("owner=$owner");
        if ($audios !== [])
            $this->template->audios = iterator_to_array($audios);

        if ($playlists !== [])
            $this->template->playlists = iterator_to_array($playlists);
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
        $nsfw      = ($this->postParam("nsfw") ?? "off") === "on";
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
                $audio->listen($this->user->identity);
            }

            $this->returnJson(["response" => true]);
        }
    }

    function renderSearch(): void
    {
        if ($this->queryParam("q")) {
            $this->template->q = $this->queryParam("q");
            $this->template->by_performer = $this->queryParam("by_performer") === "on";
            $this->template->audios = iterator_to_array($this->audios->search($this->template->q, 1, $this->template->by_performer));
            $this->template->playlists = iterator_to_array($this->audios->searchPlaylists($this->template->q));
        }
    }

    function renderNewPlaylist(): void
    {
        $owner = $this->user->id;

        if ($this->requestParam("owner")) {
            $club = (new Clubs)->get((int) $this->requestParam("owner") * -1);
            if (!$club || $club->isBanned() || !$club->canBeModifiedBy($this->user->identity))
                $this->redirect("/audios" . $this->user->id);

            $owner = ($club->getId() * -1);
        }

        $this->template->owner = $owner;

        // exit(var_dump($owner));

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $title = $this->postParam("title");
            $description = $this->postParam("description");
            $audios = !empty($this->postParam("audios")) ? explode(",", $this->postParam("audios")) : [];

            if (!$title)
                $this->returnJson(["success" => false, "error" => "Название не указано"]);

            $playlist = new Playlist;
            $playlist->setOwner($owner);
            $playlist->setName(substr($title, 0, 128));
            $playlist->setDescription(substr($description, 0, 2048));
            $playlist->save();

            foreach ($audios as $audio) {
                DatabaseConnection::i()->getContext()->query("INSERT INTO `playlist_relations` (`collection`, `media`) VALUES (?, ?)", $playlist->getId(), $audio);
            }

            DatabaseConnection::i()->getContext()->query("INSERT INTO `playlist_imports` (`entity`, `playlist`) VALUES (?, ?)", $owner, $playlist->getId());

            $this->returnJson(["success" => true, "payload" => "/playlist" . $owner . "_" . $playlist->getId()]);
        } else {
            $this->template->audios = iterator_to_array($this->audios->getByUser($this->user->identity));
        }
    }

    function renderPlaylist(int $owner_id, int $virtual_id): void
    {
        $playlist = $this->audios->getPlaylistByOwnerAndVID($owner_id, $virtual_id);

        if (!$playlist || $playlist->isDeleted())
            $this->notFound();

        $this->template->playlist = $playlist;
        $this->template->audios = iterator_to_array($playlist->getAudios());
        $this->template->isMy = $playlist->getOwner()->getId() === $this->user->id;
        $this->template->canEdit = ($this->template->isMy || ($playlist->getOwner() instanceof Club && $playlist->getOwner()->canBeModifiedBy($this->user->identity)));
        $this->template->edit = $this->queryParam("act") === "edit";

        if ($this->template->edit) {
            if (!$this->template->canEdit) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }

            $_ids = [];
            $audios = iterator_to_array($playlist->getAudios());
            foreach ($audios as $audio) {
                $_ids[] = $audio->getId();
            }

            foreach ($this->audios->getByUser($this->user->identity) as $audio) {
                if (!in_array($audio->getId(), $_ids)) {
                    $audios[] = $audio;
                }
            }

            $this->template->audios = $audios;
        } else {
            $this->template->audios = iterator_to_array($playlist->getAudios());
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
        }
    }

    function renderAction(int $audio_id): void
    {
        switch ($this->queryParam("act")) {
            case "add":
                if (!$this->audios->isAdded($this->user->id, $audio_id)) {
                    DatabaseConnection::i()->getContext()->query("INSERT INTO `audio_relations` (`entity`, `audio`) VALUES (?, ?)", $this->user->id, $audio_id);
                } else {
                    $this->returnJson(["success" => false, "error" => "Аудиозапись уже добавлена"]);
                }
                break;

            case "remove":
                if ($this->audios->isAdded($this->user->id, $audio_id)) {
                    DatabaseConnection::i()->getContext()->query("DELETE FROM `audio_relations` WHERE `entity` = ? AND `audio` = ?", $this->user->id, $audio_id);
                } else {
                    $this->returnJson(["success" => false, "error" => "Аудиозапись не добавлена"]);
                }
                break;

            case "edit":
                $audio = $this->audios->get($audio_id);
                if (!$audio || $audio->isDeleted() || $audio->isWithdrawn() || $audio->isUnlisted())
                    $this->returnJson(["success" => false, "error" => "Аудиозапись не найдена"]);

                if ($audio->getOwner()->getId() !== $this->user->id)
                    $this->returnJson(["success" => false, "error" => "Ошибка доступа"]);

                $performer = $this->postParam("performer");
                $name      = $this->postParam("name");
                $lyrics    = $this->postParam("lyrics");
                $genre     = empty($this->postParam("genre")) ? "undefined" : $this->postParam("genre");
                $nsfw      = ($this->postParam("nsfw") ?? "off") === "on";
                if(empty($performer) || empty($name) || iconv_strlen($performer . $name) > 128) # FQN of audio must not be more than 128 chars
                    $this->flashFail("err", tr("error"), tr("error_insufficient_info"));

                $audio->setName($name);
                $audio->setPerformer($performer);
                $audio->setLyrics(empty($lyrics) ? NULL : $lyrics);
                $audio->setGenre($genre);
                $audio->save();
                break;

            default:
                break;
        }

        $this->returnJson(["success" => true]);
    }
}