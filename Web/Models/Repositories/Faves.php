<?php

declare(strict_types=1);

namespace openvk\Web\Models\Repositories;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;

class Faves
{
    private $context;
    private $likes;
    
    public function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->likes = $this->context->table("likes");
    }

    private function fetchLikes(User $user, string $class = 'Post')
    {
        $fetch = $this->likes->where([
            "model"  => "openvk\\Web\\Models\\Entities\\".$class,
            "origin" => $user->getRealId(),
        ]);

        return $fetch;
    }

    public function fetchLikesSection(User $user, string $class = 'Post', int $page = 1, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $fetch = $this->fetchLikes($user, $class)->page($page, $perPage)->order("index DESC");
        foreach ($fetch as $like) {
            $className = "openvk\\Web\\Models\\Repositories\\" . $class . "s";
            $repo = new $className();
            if (!$repo) {
                continue;
            }

            $entity = $repo->get($like->target);
            yield $entity;
        }
    }

    public function fetchLikesSectionCount(User $user, string $class = 'Post')
    {
        return $this->fetchLikes($user, $class)->count();
    }
}
