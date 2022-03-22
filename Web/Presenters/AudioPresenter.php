<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Audio;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\User;
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
        $this->renderApp("_popular");
    }

    function renderNew(): void
    {
        $this->renderApp("_new");
    }

    function renderList(int $owner): void
    {
        $entity = NULL;
        if($owner < 0)
            $entity = (new Clubs)->get($owner);
        else
            $entity = (new Users)->get($owner);

        if(!$entity)
            $this->notFound();

        $this->renderApp("owner=$owner");
    }

    function renderView(int $owner, int $id): void
    {
        $this->assertUserLoggedIn();

        $audio = $this->audios->getByOwnerAndVID($owner, $id);
        if(!$audio || $audio->isDeleted())
            $this->notFound();

        if(!$audio->canBeViewedBy($this->user->identity))
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));

        $this->renderApp("id=" . $audio->getId());
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
}