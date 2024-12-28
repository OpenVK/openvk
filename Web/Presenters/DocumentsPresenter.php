<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Documents;
use openvk\Web\Models\Entities\Document;

final class DocumentsPresenter extends OpenVKPresenter
{
    protected $presenterName = "documents";
    protected $silent = true;

    function renderList(?int $gid = NULL): void
    {
        $this->template->_template = "Documents/List.xml";
    }

    function renderListGroup(?int $gid)
    {
        $this->renderList($gid);
    }

    function renderUpload()
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        $group  = NULL;
        $isAjax = $this->postParam("ajax", false) == 1;
        $ref    = $this->postParam("referrer", false) ?? "user";

        if(!is_null($this->queryParam("gid"))) {
            $gid   = (int) $this->queryParam("gid");
            $group = (new Clubs)->get($gid);
            if(!$group)
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);

            if(!$group->canUploadDocs($this->user->identity))
                $this->flashFail("err", tr("forbidden"), tr("not_enough_permissions_comment"), null, $isAjax);
        }

        $this->template->group = $group;
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;

        $owner = $this->user->id;
        if($group) {
            $owner = $group->getRealId();
        }
        
        $upload = $_FILES["blob"];
        $name = $this->postParam("name");
        $tags = $this->postParam("tags");
        $folder = $this->postParam("folder");
        $owner_hidden = ($this->postParam("owner_hidden") ?? "off") === "on";

        $document = new Document;
        $document->setOwner($owner);
        $document->setName($name);
        $document->setFolder_id($folder);
        $document->setTags(empty($tags) ? NULL : $tags);
        $document->setOwner_hidden($owner_hidden);
        $document->setFile([
            "tmp_name" => $upload["tmp_name"],
            "error"    => $upload["error"],
            "name"     => $upload["name"],
            "size"     => $upload["size"],
            "preview_owner" => $this->user->id,
        ]);

        $document->save();
    }
}
