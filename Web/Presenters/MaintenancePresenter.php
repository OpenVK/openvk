<?php
declare(strict_types=1);

namespace openvk\Web\Presenters;

final class MaintenancePresenter extends OpenVKPresenter
{
    protected $presenterName = "maintenance";

    function renderSection(string $name): void
    {
        if(!OPENVK_ROOT_CONF["openvk"]["preferences"]["maintenanceMode"][$name])
            $this->flashFail("err", tr("error"), tr("forbidden"));

        $this->template->name = [
            "photos" => tr("my_photos"),
            "videos" => tr("my_videos"),
            "messenger" => tr("my_messages"),
            "user" => tr("users"),
            "group" => tr("my_groups"),
            "comment" => tr("comments"),
            "gifts" => tr("gifts"),
            "apps" => tr("apps"),
            "notes" => tr("my_notes"),
            "notification" => tr("my_feedback"),
            "support" => tr("menu_support"),
            "topics" => tr("topics")
        ][$name] ?? $name;
    }

    function renderAll(): void
    {

    }
}
