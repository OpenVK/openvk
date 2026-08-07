<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Photos, Videos};
use Chandler\Database\DatabaseConnection;

final class Utils extends VKAPIRequestHandler
{
    public function getServerTime(): int
    {
        return time();
    }

    public function resolveScreenName(string $screen_name): object
    {
        if (\Chandler\MVC\Routing\Router::i()->getMatchingRoute("/$screen_name")[0]->presenter !== "UnknownTextRouteStrategy") {
            if (substr($screen_name, 0, strlen("id")) === "id") {
                return (object) [
                    "object_id" => (int) substr($screen_name, strlen("id")),
                    "type"      => "user",
                ];
            } elseif (substr($screen_name, 0, strlen("club")) === "club") {
                return (object) [
                    "object_id" => (int) substr($screen_name, strlen("club")),
                    "type"      => "group",
                ];
            } else {
                $this->fail(104, "Not found");
            }
        } else {
            $user = (new Users())->getByShortURL($screen_name);
            if ($user) {
                return (object) [
                    "object_id" => $user->getId(),
                    "type"      => "user",
                ];
            }

            $club = (new Clubs())->getByShortURL($screen_name);
            if ($club) {
                return (object) [
                    "object_id" => $club->getId(),
                    "type"      => "group",
                ];
            }

            $this->fail(104, "Not found");
        }
    }

    public function resolveGuid(string $guid): object
    {
        $user = (new Users())->getByChandlerUserId($guid);
        if (is_null($user)) {
            $this->fail(104, "Not found");
        }

        return $user->toVkApiStruct($this->getUser());
    }

    public function resolveAttachments(string $attachments, int $allow_type = 0): array
    {
        $this->requireUser();

        $allowTypes = ["photo", "video", "note", "audio"];
        if ($allow_type == 0) {
            $allowTypes = ["photo", "video", "doc", "audio", "wall"];
        }

        $a = parseAttachments($attachments, $allowTypes);
        $r = [];

        foreach ($a as $item) {
            if ($item && method_exists($item, "toApiAttachment") && $item->canBeViewedBy($this->getUser())) {
                $r[] = $item->toApiAttachment($this->getUser());
            } else {
                $r[] = [
                    "type" => "unknown",
                    "unknown" => []
                ];
            }
        }

        return $r;
    }

    public function resolveOffset(int $owner_id, int $id, ?int $id2 = null, string $method = "wall.get", int $perPage = 10, bool $rev = false) 
    {
        $this->requireUser();

        if ($perPage <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        }

        $exactOffset = 0;

        switch ($method) {
            case "wall.get":
                $posts = new Posts();

                $target = $posts->getPostById($owner_id, $id);
                if (!$target) {
                    $this->fail(100, "One of the parameters specified was missing or invalid");
                }

                $pinPost = $posts->getPinnedPost($owner_id);
                $hasPin  = !is_null($pinPost) && !$pinPost->isDeleted() && !$pinPost->isArchived();

                if ($hasPin && $pinPost->getVirtualId() === $id) {
                    return 0;
                }

                $created = $target->getPublicationTime()->timestamp();
                $newer   = DatabaseConnection::i()->getContext()
                            ->table("posts")
                            ->where("wall", $owner_id)
                            ->where("created > ?", $created)
                            ->where("pinned", false)
                            ->where("deleted", false)
                            ->where("suggested", 0)
                            ->where("archived", false)
                            ->count("*");

                $exactOffset = $newer + ($hasPin ? 1 : 0);

                break;
            case "photos.get":
                $photo = (new Photos)->getByOwnerAndVIDUnsafe($owner_id, $id);
                if (!$photo) {
                    $this->fail(100, "One of the parameters specified was missing or invalid");
                }

                $exactOffsetQuery = DatabaseConnection::i()->getContext()
                        ->table("album_relations")
                        ->where("collection", $id2)
                        ->where("media", $photo->getId())
                        ->fetch();

                if (!$exactOffsetQuery) {
                    $this->fail(-9, "Missing relation");
                }

                $exactOffset = DatabaseConnection::i()->getContext()
                    ->table("album_relations")
                    ->where("collection", $id2);

                if ($rev) {
                    $exactOffset = $exactOffset->where("index > ?", $exactOffsetQuery->index);
                } else {
                    $exactOffset = $exactOffset->where("index < ?", $exactOffsetQuery->index);
                }

                $exactOffset = $exactOffset->count("*");

                break;
            case "video.get":
                # no relations now

                $exactOffset = DatabaseConnection::i()->getContext()
                    ->table("videos")
                    ->where("owner", $owner_id)
                    ->where("virtual_id < ?", $id)
                    ->count("*");

                break;
            default:
                $this->fail(-5, "Unknown entity");
        }

        $div = $exactOffset / $perPage;

        if ($div > 0.5) {
            return ceil($exactOffset / $perPage) * $perPage;
        } else {
            return intdiv($exactOffset, $perPage) * $perPage;
        }
    }
}

