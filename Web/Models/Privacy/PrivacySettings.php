<?php

declare(strict_types=1);

namespace openvk\Web\Models\Privacy;

class PrivacySettings
{
    public static function getPossibleSettings(): array
    {
        return [
            "page.read",
            "page.info.read",
            "groups.read",
            "photos.read",
            "videos.read",
            "notes.read",
            "friends.read",
            "friends.add",
            "wall.write",
            "messages.write",
            "audios.read",
            "likes.read",
            "messages.add_to_chats",
            "gifts.read",
            "page.online.read",
        ];
    }
}
