<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use openvk\Web\Models\Entities\{Album, Audio, Club, Comment, Document, Note, Photo, Poll, Post, Topic, User, Video};
use openvk\Web\Models\Repositories\{Albums, Audios, Clubs, Comments, Documents, Notes, Photos, Polls, Posts, Topics, Users, Videos};

/**
 * Per-entity country restrictions.
 *
 * A block applies to the entity itself and to its owner (a profile or a group).
 * Media owned by a blocked profile disappears together with the profile, but
 * the profile's avatar remains visible so the rest of the UI does not break.
 */
final class GeoBlock
{
    private const TYPE_MAP = [
        "comment"  => Comment::class,
        "post"     => Post::class,
        "topic"    => Topic::class,
        "note"     => Note::class,
        "document" => Document::class,
        "video"    => Video::class,
        "photo"    => Photo::class,
        "audio"    => Audio::class,
        "album"    => Album::class,
        "poll"     => Poll::class,
        "user"     => User::class,
        "club"     => Club::class,
    ];

    private static ?self $instance = null;

    private $context;
    private bool $adminBypass = false;
    private ?bool $enabled = null;
    private array $blockCache = [];
    private array $avatarCache = [];

    private function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
    }

    public static function i(): self
    {
        return self::$instance ??= new self();
    }

    public function isEnabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        $config = OPENVK_ROOT_CONF["openvk"]["preferences"]["geo"] ?? [];

        return $this->enabled = (bool) ($config["enabled"] ?? false);
    }

    public function setAdminBypass(bool $enabled): void
    {
        $this->adminBypass = $enabled;
    }

    public function isAdminBypass(): bool
    {
        return $this->adminBypass;
    }

    public function country(): ?string
    {
        return GeoIp::i()->country();
    }

    public function isBlocked(object $entity, ?string $country = null, bool $allowAvatar = false): bool
    {
        if (!$this->isEnabled() || $this->adminBypass) {
            return false;
        }

        $country ??= $this->country();
        if ($country === null) {
            return false;
        }

        $type = $this->entityType($entity);
        if ($type === null) {
            return false;
        }

        if ($this->isDirectlyBlocked($type, $this->entityId($entity), $country)) {
            return true;
        }

        if ($entity instanceof Comment) {
            $target = $entity->getTarget();
            if ($target instanceof \openvk\Web\Models\RowModel && $this->isBlocked($target, $country)) {
                return true;
            }
        }

        if ($entity instanceof Poll) {
            $post = $entity->getAttachedPost();
            if ($post !== null && $this->isBlocked($post, $country)) {
                return true;
            }
        }

        foreach ($this->ownerRefs($entity) as $ref) {
            if (!$this->isDirectlyBlocked($ref["type"], $ref["id"], $country)) {
                continue;
            }

            if ($allowAvatar && $entity instanceof Photo && $this->isAvatarPhoto($entity)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function isDirectlyBlocked(string $type, int $id, string $country): bool
    {
        $country = strtoupper(trim($country));
        $key = "$type:$id:$country";

        if (array_key_exists($key, $this->blockCache)) {
            return $this->blockCache[$key];
        }

        return $this->blockCache[$key] = $this->context->table("geo_blocks")
            ->where("entity_type", $type)
            ->where("entity_id", $id)
            ->where("country_code", $country)
            ->where("revoked_at", null)
            ->count("*") > 0;
    }

    public function entityType(object $entity): ?string
    {
        foreach (self::TYPE_MAP as $type => $class) {
            if ($entity instanceof $class) {
                return $type;
            }
        }

        return null;
    }

    public function entityId(object $entity): int
    {
        return (int) $entity->getId();
    }

    /**
     * @return array<int, array{type: string, id: int}>
     */
    public function ownerRefs(object $entity): array
    {
        $refs = [];
        $add = function (int $owner) use (&$refs): void {
            if ($owner === 0) {
                return;
            }

            $refs[] = $owner > 0
                ? ["type" => "user", "id" => $owner]
                : ["type" => "club", "id" => abs($owner)];
        };

        if ($entity instanceof User) {
            $refs[] = ["type" => "user", "id" => $entity->getId()];
        } elseif ($entity instanceof Club) {
            $refs[] = ["type" => "club", "id" => $entity->getId()];
        } elseif ($entity instanceof Post) {
            $data = $entity->unwrap();
            $add((int) ($data->wall ?? 0));
            $add((int) ($data->owner ?? 0));
        } elseif ($entity instanceof Topic) {
            $data = $entity->unwrap();
            $group = (int) ($data->group ?? 0);
            if ($group !== 0) {
                $refs[] = ["type" => "club", "id" => abs($group)];
            }
            $add((int) ($data->owner ?? 0));
        } else {
            $data = $entity->unwrap();
            $add((int) ($data->owner ?? 0));
        }

        $unique = [];
        foreach ($refs as $ref) {
            $unique[$ref["type"] . ":" . $ref["id"]] = $ref;
        }

        return array_values($unique);
    }

    public function isAvatarPhoto(Photo $photo): bool
    {
        $id = $photo->getId();
        if (array_key_exists($id, $this->avatarCache)) {
            return $this->avatarCache[$id];
        }

        return $this->avatarCache[$id] = $this->context->table("album_relations")
            ->where("media", $id)
            ->where("collection IN (SELECT id FROM albums WHERE special_type = 16)")
            ->count("*") > 0;
    }

    /**
     * Direct /blob_* requests only carry a hash, so we have to resolve it back
     * to a media entity before checking the block.
     */
    public function isBlobBlocked(string $hash, ?string $country = null): bool
    {
        if (!$this->isEnabled() || $this->adminBypass) {
            return false;
        }

        $country ??= $this->country();
        if ($country === null || $hash === "") {
            return false;
        }

        $rows = $this->context->query(
            "SELECT 'photo' AS type, id FROM photos WHERE hash = ?"
            . " UNION ALL SELECT 'video' AS type, id FROM videos WHERE hash = ?"
            . " UNION ALL SELECT 'document' AS type, id FROM documents WHERE hash = ?"
            . " UNION ALL SELECT 'audio' AS type, id FROM audios WHERE hash = ?",
            $hash,
            $hash,
            $hash,
            $hash
        )->fetchAll();

        foreach ($rows as $row) {
            $entity = $this->getEntityById((string) $row->type, (int) $row->id);
            if ($entity !== null && $this->isBlocked($entity, $country, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Excludes directly blocked entities from a Nette selection.
     */
    public function excludeEntity(Selection $query, string $table, string $type, ?string $country = null): void
    {
        $country ??= $this->country();
        if (!$this->isEnabled() || $this->adminBypass || $country === null) {
            return;
        }

        $query->where(
            "NOT EXISTS (SELECT 1 FROM geo_blocks gb WHERE gb.revoked_at IS NULL"
            . " AND gb.country_code = ? AND gb.entity_type = ? AND gb.entity_id = $table.id)",
            strtoupper(trim($country)),
            $type
        );
    }

    /**
     * Excludes content whose signed `owner` column points to a blocked profile
     * or group. Negative owner values are group ids.
     */
    public function excludeOwner(Selection $query, string $column, ?string $country = null): void
    {
        $country ??= $this->country();
        if (!$this->isEnabled() || $this->adminBypass || $country === null) {
            return;
        }

        $query->where(
            "NOT EXISTS (SELECT 1 FROM geo_blocks gb WHERE gb.revoked_at IS NULL"
            . " AND gb.country_code = ? AND ((gb.entity_type = 'user' AND gb.entity_id = $column)"
            . " OR (gb.entity_type = 'club' AND gb.entity_id = ABS($column))))",
            strtoupper(trim($country))
        );
    }

    /**
     * @param iterable<object> $entities
     * @return \Generator<object>
     */
    public function filterEntities(iterable $entities): \Generator
    {
        foreach ($entities as $entity) {
            if (!$this->isBlocked($entity)) {
                yield $entity;
            }
        }
    }

    /**
     * @return array<int, ActiveRow>
     */
    public function getActiveBlocks(?string $country = null): array
    {
        $query = $this->context->table("geo_blocks")->where("revoked_at", null);
        if ($country !== null) {
            $query->where("country_code", strtoupper(trim($country)));
        }

        return $query->order("created_at DESC")->fetchAll();
    }

    /**
     * @return string[]
     */
    public function getDefaultCountries(): array
    {
        $config = OPENVK_ROOT_CONF["openvk"]["preferences"]["geo"] ?? [];
        $countries = $config["defaultCountries"] ?? ["RU"];

        return is_array($countries) && $countries !== [] ? array_values($countries) : ["RU"];
    }

    /**
     * @return array<string, int> country code => inserted row id
     */
    public function block(string $type, int $id, array $countries, string $reason, ?string $externalRef = null, ?int $actor = null, ?int $parentId = null): array
    {
        if (!isset(self::TYPE_MAP[$type])) {
            throw new \InvalidArgumentException("Unknown geo block type: $type");
        }
        if ($id <= 0) {
            throw new \InvalidArgumentException("Entity id must be positive");
        }
        if (trim($reason) === "") {
            throw new \InvalidArgumentException("Reason is required");
        }

        $inserted = [];
        foreach ($countries as $country) {
            $country = strtoupper(trim((string) $country));
            if (preg_match("/^[A-Z]{2}$/", $country) !== 1) {
                throw new \InvalidArgumentException("Invalid country code: $country");
            }

            $existing = $this->context->table("geo_blocks")
                ->where("entity_type", $type)
                ->where("entity_id", $id)
                ->where("country_code", $country)
                ->where("revoked_at", null);

            if ($parentId === null) {
                $existing->where("parent_id", null);
            } else {
                $existing->where("parent_id", $parentId);
            }

            if ($existing->count("*") > 0) {
                continue;
            }

            $row = $this->context->table("geo_blocks")->insert([
                "entity_type"  => $type,
                "entity_id"    => $id,
                "country_code" => $country,
                "reason"       => $reason,
                "external_ref" => $externalRef,
                "parent_id"    => $parentId,
                "created_by"   => $actor ?? 0,
                "created_at"   => time(),
            ]);

            $inserted[$country] = (int) $row->id;
        }

        $this->blockCache = [];

        return $inserted;
    }

    public function getBlockById(int $id): ?ActiveRow
    {
        return $this->context->table("geo_blocks")->get($id);
    }

    public function getActiveBlock(string $type, int $id, string $country): ?ActiveRow
    {
        return $this->context->table("geo_blocks")
            ->where("entity_type", $type)
            ->where("entity_id", $id)
            ->where("country_code", strtoupper(trim($country)))
            ->where("parent_id", null)
            ->where("revoked_at", null)
            ->fetch();
    }

    public function unblock(string $type, int $id, array $countries, string $reason, ?int $actor = null): int
    {
        if (trim($reason) === "") {
            throw new \InvalidArgumentException("Reason is required");
        }

        $countries = array_values(array_unique(array_map(
            fn($country): string => strtoupper(trim((string) $country)),
            $countries
        )));

        if ($countries === []) {
            return 0;
        }

        $rows = $this->context->table("geo_blocks")
            ->where("entity_type", $type)
            ->where("entity_id", $id)
            ->where("country_code IN (?)", $countries)
            ->where("revoked_at", null)
            ->fetchAll();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
        }

        if (empty($ids)) {
            return 0;
        }

        $data = [
            "revoked_by"    => $actor ?? 0,
            "revoked_at"    => time(),
            "revoke_reason" => $reason,
        ];

        $this->context->table("geo_blocks")->where("id IN (?)", $ids)->update($data);
        $this->context->table("geo_blocks")
            ->where("parent_id IN (?)", $ids)
            ->where("revoked_at", null)
            ->update($data);

        $this->blockCache = [];

        return count($ids);
    }

    /**
     * Resolves an admin/CLI target: a pretty OpenVK URL, a full URL, or type:id.
     *
     * @return array{type: string, id: int, entity: object, label: string}|null
     */
    public function resolveTarget(string $input): ?array
    {
        $input = trim($input);
        if ($input === "") {
            return null;
        }

        if (preg_match("/^([a-z]+):(\d+)$/", $input, $matches) === 1) {
            $type = $matches[1];
            $id = (int) $matches[2];
            $entity = $this->getEntityById($type, $id);

            return $entity === null ? null : [
                "type"   => $type,
                "id"     => $id,
                "entity" => $entity,
                "label"  => $this->label($type, $id, $entity),
            ];
        }

        if (preg_match("#^https?://#i", $input) === 1) {
            $path = parse_url($input, PHP_URL_PATH);
            if (is_string($path) && $path !== "") {
                $input = $path;
            }
        }

        $input = "/" . ltrim($input, "/");
        $input = preg_replace("/[?#].*$/", "", $input) ?? $input;
        $input = rtrim($input, "/");

        $entity = null;
        $type = null;

        if (preg_match("#^/wall(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Posts())->getPostById((int) $matches[1], (int) $matches[2]);
            $type = "post";
        } elseif (preg_match("#^/note(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Notes())->getNoteById((int) $matches[1], (int) $matches[2]);
            $type = "note";
        } elseif (preg_match("#^/doc(\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Documents())->getDocumentById((int) $matches[1], (int) $matches[2]);
            $type = "document";
        } elseif (preg_match("#^/video(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Videos())->getByOwnerAndVID((int) $matches[1], (int) $matches[2], null, true);
            $type = "video";
        } elseif (preg_match("#^/photo(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Photos())->getByOwnerAndVID((int) $matches[1], (int) $matches[2]);
            $type = "photo";
        } elseif (preg_match("#^/audio(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Audios())->get((int) $matches[2]);
            $type = "audio";
        } elseif (preg_match("#^/topic(\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Topics())->getTopicById((int) $matches[1], (int) $matches[2]);
            $type = "topic";
        } elseif (preg_match("#^/album(-?\d+)_(\d+)$#", $input, $matches) === 1) {
            $entity = (new Albums())->get((int) $matches[2]);
            $type = "album";
        } elseif (preg_match("#^/poll(\d+)$#", $input, $matches) === 1) {
            $entity = (new Polls())->get((int) $matches[1]);
            $type = "poll";
        } elseif (preg_match("#^/id(\d+)$#", $input, $matches) === 1) {
            $entity = (new Users())->get((int) $matches[1]);
            $type = "user";
        } elseif (preg_match("#^/(?:club|public)(\d+)$#", $input, $matches) === 1) {
            $entity = (new Clubs())->get((int) $matches[1]);
            $type = "club";
        }

        if ($entity === null || $type === null) {
            return null;
        }

        $id = $this->entityId($entity);

        return [
            "type"   => $type,
            "id"     => $id,
            "entity" => $entity,
            "label"  => $this->label($type, $id, $entity),
        ];
    }

    private function getEntityById(string $type, int $id): ?object
    {
        return match ($type) {
            "post"     => (new Posts())->get($id),
            "note"     => (new Notes())->get($id),
            "document" => (new Documents())->get($id),
            "video"    => (new Videos())->get($id),
            "photo"    => (new Photos())->get($id),
            "audio"    => (new Audios())->get($id),
            "album"    => (new Albums())->get($id),
            "topic"    => (new Topics())->get($id),
            "poll"     => (new Polls())->get($id),
            "comment"  => (new Comments())->get($id),
            "user"     => (new Users())->get($id),
            "club"     => (new Clubs())->get($id),
            default    => null,
        };
    }

    private function label(string $type, int $id, object $entity): string
    {
        if (method_exists($entity, "getPrettyId")) {
            return $type . ":" . $entity->getPrettyId();
        }

        return $type . ":" . $id;
    }
}
