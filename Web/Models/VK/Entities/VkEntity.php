<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Entities;
abstract class VkEntity
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function getId(): int { return (int) ($this->data["id"] ?? 0); }
    public function getOwnerId(): int { return (int) ($this->data["owner_id"] ?? 0); }
    public function getPrettyId(): string { return $this->getOwnerId() . "_" . $this->getId(); }
    public function canBeModifiedBy($user): bool
    {
        return $this->getOwnerId() == $user->getId();
    }

    public function getOwner(): User|Club|null
    {
        $oid = $this->getOwnerId();
        if ($oid > 0) return User::load($oid);
        if ($oid < 0) return Club::load(abs($oid));
        if ($oid == 0) {
            return User::load(100);
        }

        return null;
    }

    /**
     * Возвращает дату как Unix timestamp.
     * Аналог getTime() из оригинального Post.
     */
    public function getTime(): ?int
    {
        return isset($this->data["date"]) ? (int) $this->data["date"] : null;
    }

    /**
     * Возвращает лайки в VK-формате.
     */
    public function getLikes(): ?array
    {
        return $this->data["likes"] ?? null;
    }

    public function getLikesCount(): int
    {
        return (int) ($this->getLikes()["count"] ?? 0);
    }

    public function getUserLikes(): bool
    {
        return (bool) ($this->getLikes()["user_likes"] ?? false);
    }

    public function canBeViewedBy($user = null): bool
    {
        return true;
    }

    public function hasLikeFrom($user): bool
    {
        // Данные уже в response от wall.get — user_likes из объекта likes
        return (bool) (($this->data["likes"] ?? [])["user_likes"] ?? false);
    }


    /**
     * Ставит лайк от имени пользователя.
     *
     * Возвращает true при успешном добавлении лайка.
     * Если лайк уже был поставлен (ошибка VK API), исключение перехватывается.
     */
    public function like(User $user): bool
    {
        try {
            VKAPIClient::i()->call("likes.add", [
                "type"     => "post",
                "owner_id" => $this->getOwnerId(),
                "item_id"  => $this->getId(),
            ]);

            return true;
        } catch (VKAPIException $e) {
            return false;
        }
    }

    /**
     * Создаёт комментарий к посту от имени пользователя.
     *
     * @return array Ответ VK API (содержит comment_id и другие поля)
     */
    public function createComment(User $user, string $text): array
    {
        $response = VKAPIClient::i()->call("wall.createComment", [
            "owner_id" => $this->getOwnerId(),
            "post_id"  => $this->getId(),
            "message"  => $text,
        ]);

        return $response;
    }

    public function toggleLike($user): bool
    {
        $isLiked = $this->hasLikeFrom($user);
        try {
            if ($isLiked) {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.delete", [
                    "type" => "post",
                    "owner_id" => $this->getOwnerId(),
                    "item_id" => $this->getId(),
                ]);
                return false;
            } else {
                \openvk\VKAPIClient\VKAPIClient::i()->call("likes.add", [
                    "type" => "post",
                    "owner_id" => $this->getOwnerId(),
                    "item_id" => $this->getId(),
                ]);
                return true;
            }
        } catch (\Throwable) {
            return $isLiked;
        }
    }

    /**
     * Возвращает дату публикации как объект DateTime.
     */
    public function getPublicationTime(): \openvk\Web\Util\DateTime
    {
        $ts = $this->getTime() ?? time();

        return new \openvk\Web\Util\DateTime($ts);
    }

    public function getCreationTime()
    {
        return $this->getPublicationTime();
    }

    public function canBeDeletedBy($who): bool
    {
        return false;
    }

    public function isDeleted(): bool { return false; }
    public function getRawData(): array { return $this->data; }

    public function getComments(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        // Determine API method based on entity type
        $method = "wall.getComments"; // default for Post
        $idParam = "post_id";

        if ($this instanceof \openvk\Web\Models\VK\Entities\Photo) {
            $method = "photos.getComments";
            $idParam = "photo_id";
        } elseif ($this instanceof \openvk\Web\Models\VK\Entities\Video) {
            $method = "video.getComments";
            $idParam = "video_id";
        } elseif ($this instanceof \openvk\Web\Models\VK\Entities\Topic) {
            $method = "board.getComments";
            $idParam = "topic_id";
        }

        try {
            $response = \openvk\VKAPIClient\VKAPIClient::i()->call($method, [
                "owner_id" => $this->getOwnerId(),
                $idParam   => $this->getId(),
                "count"    => min($perPage, 100),
                "offset"   => $offset,
                "extended" => 1,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $comments = [];
        foreach ($response["items"] ?? [] as $item) {
            $comments[] = new \openvk\Web\Models\VK\Entities\Comment($item);
        }

        return $comments;
    }

    public function getCommentsCount(): int
    {
        $method = "wall.getComments";
        $idParam = "post_id";

        if ($this instanceof \openvk\Web\Models\VK\Entities\Photo) {
            $method = "photos.getComments";
            $idParam = "photo_id";
        } elseif ($this instanceof \openvk\Web\Models\VK\Entities\Video) {
            $method = "video.getComments";
            $idParam = "video_id";
        }

        try {
            $response = \openvk\VKAPIClient\VKAPIClient::i()->call($method, [
                "owner_id" => $this->getOwnerId(),
                $idParam   => $this->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return $response["count"] ?? 0;
    }

    public function getLastComments(int $count = 3): array
    {
        return $this->getComments(1, $count);
    }

    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
}
