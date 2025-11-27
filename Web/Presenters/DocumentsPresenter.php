<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\{Documents, Clubs};
use openvk\Web\Models\Entities\Document;
use Nette\InvalidStateException as ISE;

final class DocumentsPresenter extends OpenVKPresenter
{
    protected $presenterName = "documents";
    protected $silent = true;

    public function renderList(?int $owner_id = null): void
    {
        $this->assertUserLoggedIn();

        $this->template->_template = "Documents/List.latte";
        if ($owner_id > 0) {
            $this->notFound();
        }

        if ($owner_id < 0) {
            $owner = (new Clubs())->get(abs($owner_id));
            if (!$owner || $owner->isBanned()) {
                $this->notFound();
            } else {
                $this->template->group = $owner;
            }
        }

        if (!$owner_id) {
            $owner_id = $this->user->id;
        }

        $current_tab   = (int) ($this->queryParam("tab") ?? 0);
        $current_order = (int) ($this->queryParam("order") ?? 0);
        $page  = (int) ($this->queryParam("p") ?? 1);
        $order = in_array($current_order, [0,1,2]) ? $current_order : 0;
        $tab   = in_array($current_tab, [0,1,2,3,4,5,6,7,8]) ? $current_tab : 0;

        $api_request = $this->queryParam("picker") == "1";
        if ($api_request && $_SERVER["REQUEST_METHOD"] === "POST") {
            $ctx_type = $this->postParam("context");
            $docs = null;

            switch ($ctx_type) {
                default:
                case "list":
                    $docs = (new Documents())->getDocumentsByOwner($owner_id, (int) $order, (int) $tab);
                    break;
                case "search":
                    $ctx_query = $this->postParam("ctx_query");
                    $docs = (new Documents())->find($ctx_query);
                    break;
            }

            $this->template->docs  = $docs->page($page, OPENVK_DEFAULT_PER_PAGE);
            $this->template->page  = $page;
            $this->template->count = $docs->size();
            $this->template->pagesCount = ceil($this->template->count / OPENVK_DEFAULT_PER_PAGE);
            $this->template->_template = "Documents/ApiGetContext.latte";
            return;
        }

        $docs = (new Documents())->getDocumentsByOwner($owner_id, (int) $order, (int) $tab);
        $this->template->tabs  = (new Documents())->getTypes($owner_id);
        $this->template->tags  = (new Documents())->getTags($owner_id, (int) $tab);
        $this->template->current_tab = $tab;
        $this->template->order = $order;
        $this->template->count = $docs->size();
        $this->template->docs  = iterator_to_array($docs->page($page, OPENVK_DEFAULT_PER_PAGE));
        $this->template->locale_string = "you_have_x_documents";
        if ($current_tab != 0) {
            $this->template->locale_string = "x_documents_in_tab";
        } elseif ($owner_id < 0) {
            $this->template->locale_string = "group_has_x_documents";
        }

        $this->template->canUpload = $owner_id == $this->user->id || $this->template->group->canBeModifiedBy($this->user->identity);
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $page,
            "amount"  => sizeof($this->template->docs),
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
    }

    public function renderListGroup(?int $gid)
    {
        $this->renderList($gid);
    }

    public function renderUpload()
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $group  = null;
        $isAjax = $this->postParam("ajax", false) == 1;
        $ref    = $this->postParam("referrer", false) ?? "user";

        if (!is_null($this->queryParam("gid"))) {
            $gid   = (int) $this->queryParam("gid");
            $group = (new Clubs())->get($gid);
            if (!$group || $group->isBanned()) {
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
            }

            if (!$group->canUploadDocs($this->user->identity)) {
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
            }
        }

        $this->template->group = $group;
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $owner = $this->user->id;
        if ($group) {
            $owner = $group->getRealId();
        }

        $upload = $_FILES["blob"];
        $name = $this->postParam("name");
        $tags = $this->postParam("tags");
        $folder = $this->postParam("folder");
        $owner_hidden = ($this->postParam("owner_hidden") ?? "off") === "on";

        try {
            $document = new Document();
            $document->setOwner($owner);
            $document->setName(ovk_proc_strtr($name, 255));
            $document->setFolder_id($folder);
            $document->setTags(empty($tags) ? null : $tags);
            $document->setOwner_hidden($owner_hidden);
            $document->setFile([
                "tmp_name" => $upload["tmp_name"],
                "error"    => $upload["error"],
                "name"     => $upload["name"],
                "size"     => $upload["size"],
                "preview_owner" => $this->user->id,
            ]);

            $document->save();
        } catch (\TypeError $e) {
            $this->flashFail("err", tr("forbidden"), $e->getMessage(), null, $isAjax);
        } catch (ISE $e) {
            $this->flashFail("err", tr("forbidden"), "corrupted file", null, $isAjax);
        } catch (\ValueError $e) {
            $this->flashFail("err", tr("forbidden"), $e->getMessage(), null, $isAjax);
        } catch (\ImagickException $e) {
            $this->flashFail("err", tr("forbidden"), tr("error_file_preview"), null, $isAjax);
        }

        if (!$isAjax) {
            $this->redirect("/docs" . (isset($group) ? $group->getRealId() : ""));
        } else {
            $this->returnJson([
                "success"  => true,
                "redirect" => "/docs" . (isset($group) ? $group->getRealId() : ""),
            ]);
        }
    }

    public function renderPage(int $virtual_id, int $real_id): void
    {
        $this->assertUserLoggedIn();

        $access_key = $this->queryParam("key");
        $doc = (new Documents())->getDocumentById((int) $virtual_id, (int) $real_id, $access_key);
        if (!$doc || $doc->isDeleted()) {
            $this->notFound();
        }

        if (!$doc->checkAccessKey($access_key)) {
            $this->notFound();
        }

        $this->template->doc        = $doc;
        $this->template->type       = $doc->getVKAPIType();
        $this->template->is_image   = $doc->isImage();
        $this->template->tags       = $doc->getTags();
        $this->template->copied     = $doc->isCopiedBy($this->user->identity);
        $this->template->copyImportance = true;
        $this->template->modifiable = $doc->canBeModifiedBy($this->user->identity);
    }
}
