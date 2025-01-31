<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\Report;
use openvk\Web\Models\Repositories\Reports as ReportsRepo;

final class Reports extends VKAPIRequestHandler
{
    public function add(int $owner_id = 0, string $comment = "", int $reason = 0, string $type = "", string $report_source = ""): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $allowed_types = ["post", "photo", "video", "group", "comment", "note", "app", "user", "audio"];
        if ($type == "" || !in_array($type, $allowed_types)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: type should be " . implode(", ", $allowed_types));
        }

        if ($owner_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: Bad input");
        }

        if (mb_strlen($comment) === 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: Comment can't be empty");
        }

        if ($type == "user" && $owner_id == $this->getUser()->getId()) {
            return 1;
        }

        if ($this->getUser()->isBannedInSupport()) {
            return 0;
        }

        if (sizeof(iterator_to_array((new ReportsRepo())->getDuplicates($type, $owner_id, null, $this->getUser()->getId()))) > 0) {
            return 1;
        }

        try {
            $report = new Report();
            $report->setUser_id($this->getUser()->getId());
            $report->setTarget_id($owner_id);
            $report->setType($type);
            $report->setReason($comment);
            $report->setCreated(time());

            $report->save();
        } catch (\Throwable $e) {
            $this->fail(-1, "Unknown error failed");
        }

        return 1;
    }
}
