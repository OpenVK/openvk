<?php

declare(strict_types=1);

namespace openvk\Web\Util;

/**
 * Request-scoped marker for web moderation views.
 *
 * It is enabled only by web presenters (never by VKAPI/ServiceAPI) and lets
 * entities distinguish "an admin is viewing a hidden page" from regular
 * access checks, so hidden content stays read-only outside the web UI.
 */
final class ModerationContext
{
    private static ?int $viewerId = null;

    public static function enter(int $viewerId): void
    {
        self::$viewerId = $viewerId;
    }

    public static function isActiveFor(?int $viewerId): bool
    {
        return self::$viewerId !== null && $viewerId !== null && self::$viewerId === $viewerId;
    }
}
