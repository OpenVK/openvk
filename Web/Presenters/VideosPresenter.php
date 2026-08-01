<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\Video;
use openvk\Web\Models\Repositories\{Users, Clubs, Videos};
use Nette\InvalidStateException as ISE;

final class VideosPresenter extends OpenVKPresenter
{
    private $videos;
    private $users;
    protected $presenterName = "videos";

    public function __construct(Videos $videos, Users $users)
    {
        $this->videos = $videos;
        $this->users  = $users;

        parent::__construct();
    }

    public function renderList(int $id): void
    {
        $owner = null;
        if ($id > 0) {
            $owner = $this->users->get($id);
        } else {
            $owner = (new Clubs)->get($id * -1);
        }

        if (!$owner) {
            $this->notFound();
        }

        if ($owner->getRealId() > 0 && !$owner->getPrivacyPermission('videos.read', $this->user->identity ?? null)) {
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
        }

        $this->template->user   = $owner;
        $this->template->videos = $this->videos->getByUser($owner, (int) ($this->queryParam("p") ?? 1));
        $this->template->count  = $this->videos->getUserVideosCount($owner);
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($this->queryParam("p") ?? 1),
            "amount"  => null,
            "perPage" => 7,
            "tidy"    => false,
            "atTop"   => false,
        ];
    }

    public function renderView(int $owner, int $vId): void
    {
        $video = $this->videos->getByOwnerAndVID($owner, $vId);

        if (!$video || $video->isDeleted()) {
            $this->notFound();
        }

        $user = $video->getOwner();

        if (!$user) {
            $this->notFound();
        }

        if ($user->getRealId() > 0 && !$user->getPrivacyPermission('videos.read', $this->user->identity ?? null)) {
            $this->flashFail("err", tr("forbidden"), tr("forbidden_comment"));
        }

        $this->template->user     = $user;
        $this->template->video    = $video;
        $this->template->cCount   = $this->template->video->getCommentsCount();
        $this->template->cPage    = (int) ($this->queryParam("p") ?? 1);
        $this->template->comments = iterator_to_array($this->template->video->getComments($this->template->cPage));
    }

    public function renderUpload(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        if (OPENVK_ROOT_CONF['openvk']['preferences']['videos']['disableUploading']) {
            $this->flashFail("err", tr("error"), tr("video_uploads_disabled"));
        }

        $gid = $this->queryParam("gid");
        $group = null;

        if ($gid != null) {
            $group = (new Clubs)->get((int) $gid);

            if (!$group || !$group->canBeModifiedBy($this->user->identity)) {
                $this->flashFail("err", tr("error"), tr("access_denied"));
            }
        }

        $this->template->owner = $group ? $group : $this->user->identity;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $is_ajax = (int) ($this->postParam('ajax') ?? '0') == 1;
            $is_from_messenger = (int) ($this->postParam("is_from_messenger") ?? "0") == 1;

            if (!empty($this->postParam("name"))) {
                $video = new Video();
                $video->setOwner($this->user->id);

                if ($group != null) {
                    $video->setContext($group, true);
                }

                if ($is_from_messenger) {
                    $video->setAsFromMessage();
                }

                $video->setName(ovk_proc_strtr($this->postParam("name"), 61));
                $video->setDescription(ovk_proc_strtr($this->postParam("desc"), 300));
                $video->setCreated(time());

                try {
                    if (isset($_FILES["blob"]) && file_exists($_FILES["blob"]["tmp_name"])) {
                        $video->setFile($_FILES["blob"]);
                    } elseif (!empty($this->postParam("link"))) {
                        $video->setLink($this->postParam("link"));
                    } else {
                        $this->flashFail("err", tr("no_video_error"), tr("no_video_description"), 10, $is_ajax);
                    }
                } catch (\DomainException $ex) {
                    $this->flashFail("err", tr("error_video"), tr("file_corrupted"), 10, $is_ajax);
                } catch (ISE $ex) {
                    $this->flashFail("err", tr("error_video"), tr("link_incorrect"), 10, $is_ajax);
                }

                if ((int) ($this->postParam("unlisted") ?? '0') == 1) {
                    $video->setUnlisted(true);
                }

                if ($is_from_messenger) {
                    $photo->setAsFromMessage();
                }

                $video->save();

                if ($is_ajax) {
                    $object = $video->getApiStructure();
                    $this->returnJson([
                        'payload' => $object->video,
                    ]);
                }

                $this->redirect("/video" . $video->getPrettyId());
            } else {
                $this->flashFail("err", tr("error_video"), tr("no_name_error"), 10, $is_ajax);
            }
        }
    }

    public function renderEdit(int $owner, int $vId): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $video = $this->videos->getByOwnerAndVID($owner, $vId);
        if (!$video) {
            $this->notFound();
        }
        if (is_null($this->user->identity) || !$video->canBeModifiedBy($this->user->identity)) {
            $this->flashFail("err", tr("access_denied_error"), tr("access_denied_error_description"));
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $video->setName(empty($this->postParam("name")) ? null : $this->postParam("name"));
            $video->setDescription(empty($this->postParam("desc")) ? null : $this->postParam("desc"));
            $video->makeNotUnlisted();
            $video->save();

            $this->flash("succ", tr("changes_saved"), tr("changes_saved_video_comment"));
            $this->redirect("/video" . $video->getPrettyId());
        }

        $this->template->video = $video;
        $this->template->owner = $video->getOwner();
    }

    public function renderRemove(int $owner, int $vid): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $video = $this->videos->getByOwnerAndVID($owner, $vid);
        if (!$video) {
            $this->notFound();
        }
        $user = $this->user->id;

        if (!is_null($user)) {
            if ($video->canBeModifiedBy($this->user->identity)) {
                $video->deleteVideo($owner, $vid);
            }
        } else {
            $this->flashFail("err", tr("cant_delete_video"), tr("cant_delete_video_comment"));
        }

        $this->redirect("/videos" . $owner);
    }

    public function renderLike(int $owner, int $video_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();

        $video = $this->videos->getByOwnerAndVID($owner, $video_id);
        if (!$video || $video->isDeleted() || $video->getOwner()->isDeleted()) {
            $this->notFound();
        }

        if (method_exists($video, "canBeViewedBy") && !$video->canBeViewedBy($this->user->identity)) {
            $this->flashFail("err", tr("error"), tr("forbidden"));
        }

        if (!is_null($this->user->identity)) {
            $video->toggleLike($this->user->identity);
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->returnJson([
                'success' => true,
            ]);
        }

        $this->redirect("$_SERVER[HTTP_REFERER]");
    }
}
