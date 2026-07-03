<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Repositories;

use openvk\VKAPIClient\VKAPIClient;
use openvk\Web\Models\VK\Entities\VkEntity;
use openvk\Web\Models\VK\Entities\Comment;
use openvk\Web\Models\VK\Entities\Post;
use openvk\Web\Models\VK\Entities\Photo;
use openvk\Web\Models\VK\Entities\Video;

/**
 * Репозиторий комментариев, работающий через VK API.
 *
 * Поскольку VK API не имеет универсального метода для получения
 * комментариев, используются методы wall.getComments,
 * photos.getComments и video.getComments в зависимости от типа цели.
 */
class Comments
{
    /**
     * VK API не имеет прямого аналога get(int $id)
     * для комментариев, так как comment_id уникален только в пределах
     * владельца и поста/фото/видео.
     *
     * Этот метод выбрасывает исключение — используйте getCommentsByTarget().
     */
    public function get(int $id): ?Comment
    {
        throw new \RuntimeException(
            "VK API: comment lookup requires owner context. Use getCommentsByTarget(entity) instead.",
        );
    }

    /**
     * Определяет VK API-метод и имя параметра ID для типа сущности.
     *
     * @return array{0: string, 1: string} [method, idParamName]
     */
    private static function resolveApiMethod(VkEntity $entity): array
    {
        if ($entity instanceof Post) {
            return ["wall.getComments", "post_id"];
        } elseif ($entity instanceof Photo) {
            return ["photos.getComments", "photo_id"];
        } elseif ($entity instanceof Video) {
            return ["video.getComments", "video_id"];
        }

        // По умолчанию — wall.getComments (Post)
        return ["wall.getComments", "post_id"];
    }

    /**
     * Загружает комментарии к указанной сущности.
     * VK: wall.getComments / photos.getComments / video.getComments
     *
     * @return Comment[]
     */
    public function getCommentsByTarget(VkEntity $entity, int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        [$method, $idParam] = self::resolveApiMethod($entity);

        try {
            $response = VKAPIClient::i()->call($method, [
                "owner_id" => $entity->getOwnerId(),
                $idParam   => $entity->getId(),
                "count"    => min($perPage, 100),
                "offset"   => $offset,
                "extended" => 1,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $comments = [];
        foreach ($response["items"] ?? [] as $item) {
            $comments[] = new Comment($item);
        }

        return $comments;
    }

    /**
     * Возвращает количество комментариев к указанной сущности.
     * VK: wall.getComments / photos.getComments / video.getComments с count=0
     */
    public function getCommentsCountByTarget(VkEntity $entity): int
    {
        [$method, $idParam] = self::resolveApiMethod($entity);

        try {
            $response = VKAPIClient::i()->call($method, [
                "owner_id" => $entity->getOwnerId(),
                $idParam   => $entity->getId(),
                "count"    => 0,
            ]);
        } catch (\Throwable) {
            return 0;
        }

        return $response["count"] ?? 0;
    }

    /**
     * Возвращает последние N комментариев к указанной сущности.
     * VK: wall.getComments / photos.getComments / video.getComments
     *
     * @return Comment[]
     */
    public function getLastCommentsByTarget(VkEntity $entity, int $count): array
    {
        [$method, $idParam] = self::resolveApiMethod($entity);

        try {
            $response = VKAPIClient::i()->call($method, [
                "owner_id" => $entity->getOwnerId(),
                $idParam   => $entity->getId(),
                "count"    => min($count, 100),
                "offset"   => 0,
                "extended" => 1,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $comments = [];
        foreach ($response["items"] ?? [] as $item) {
            $comments[] = new Comment($item);
        }

        return $comments;
    }
}
