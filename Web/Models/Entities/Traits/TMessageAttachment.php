<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Traits;

use openvk\Web\Models\Entities\User;
use Chandler\Database\DatabaseConnection;

trait TMessageAttachment
{
    private function isViewableByMessageParticipant(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $db     = DatabaseConnection::i()->getContext();
        $msgIds = [];

        foreach ($db->table("attachments")
            ->select("target_id")
            ->where("attachable_type", get_class($this))
            ->where("attachable_id", $this->getId())
            ->where("target_type", "openvk\\Web\\Models\\Entities\\Message") as $row) {
            $msgIds[] = $row->target_id;
        }

        if (empty($msgIds)) {
            return false;
        }

        return (bool) $db->table("messages")
            ->where("id", $msgIds)
            ->where("deleted", 0)
            ->where(
                "(sender_type = ? AND sender_id = ?) OR (recipient_type = ? AND recipient_id = ?)",
                "openvk\\Web\\Models\\Entities\\User",
                $user->getId(),
                "openvk\\Web\\Models\\Entities\\User",
                $user->getId()
            )
            ->count("*");
    }
}
