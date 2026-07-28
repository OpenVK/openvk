<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Photo};
use openvk\Web\Models\Repositories\Photos;
use PhpCsFixer\ConfigurationException\RequiredFixerConfigurationException;
use Chandler\Database\DatabaseConnection;

class Chat extends RowModel
{
    protected $tableName = "chats";
    public $hydratedData = [];

    //
    // Meta
    //

    public function getChatId(): int
    {
        return (int) ($this->getRecord()->chat_id ?? 0);
    }

    public function getChatGlobalId(): int
    {
        return $this->getChatId() + 2000000000;
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? "";
    }

    public function getDescription(): string
    {
        return $this->getRecord()->description ?? "";
    }

    public function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        if (is_null($edited)) {
            return null;
        }

        return new DateTime($edited);
    }

    public function setDescription(string $description): void
    {
        $this->stateChanges("description", $description);
    }

    public function setChatId(int $chatId): void
    {
        $this->stateChanges("chat_id", $chatId);
    }

    public function setTitle(string $title): void
    {
        $this->stateChanges("title", $title);

        # TODO: Send message about it
    }

    //
    // Avatar
    //

    public function getPhotoId(): ?int
    {
        $photoId = $this->getRecord()->photo_id;
        return $photoId !== null ? (int) $photoId : null;
    }

    public function getPhoto(): ?Photo
    {
        $photoId = $this->getPhotoId();
        if ($photoId === null) {
            return null;
        }

        $photoRepo = new Photos();

        return $photoRepo->get($photoId);
    }

    public function pushPhotoToHistory(Photo $photo): bool
    {
        $history = $this->getPhotoHistory();
        $id = $photo->getId();

        if (in_array($id, $history)) {
            return false;
        }

        array_unshift($history, $id);

        if (sizeof($history) > 100) {
            $history = array_slice($history, 0, 100);
        }

        $this->stateChanges("photos_history", implode(",", $history));

        return true;
    }

    public function removePhotoFromHistory(?Photo $photo = null): bool
    {
        $history = $this->getPhotoHistory();
        $id = $photo ? $photo->getId() : $this->getPhotoId();

        $index = array_search($id, $history);
        if ($index === false) {
            return false;
        }

        array_splice($history, $index, 1);
        $this->stateChanges("photos_history", implode(",", $history));

        return true;
    }

    public function getAvatarsHistory(bool $ids_only = false): array
    {
        $raw = $this->getRecord()->photos_history;
        if (empty($raw)) {
            return [];
        }

        if ($ids_only == true) {
            return array_map("intval", explode(",", $raw));
        }

        return (new Photos)->getByIds(explode(",", $raw));
    }

    public function deleteCurrentPhoto(): bool
    {
        $currentId = $this->getPhotoId();
        if (!$currentId) {
            return false;
        }

        $this->stateChanges("photo_id", null);

        return true;
    }

    public function getPhotoURL(string $size = "miniscule"): string | null
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        $photo = $this->getPhoto();
        if (is_null($photo)) {
            return null;
        }

        return $photo->getURLBySizeId($size);
    }

    public function hasPhoto(): bool
    {
        return $this->getPhotoId() !== null;
    }

    public function updatePhoto(?User $user, string $imagePath): Photo
    {
        $photoObj = new Photo();
        $photoObj->setOwner($user->getId());
        $photoObj->setCreated(time());
        $photoObj->setSystem(1);
        $photoObj->setAsFromMessage();
        $photoObj->setFile([
            "tmp_name" => $imagePath,
            "error"    => 0,
        ]);
        $photoObj->save();

        $this->stateChanges("photo_id", $photoObj->getId());
        $this->pushPhotoToHistory($photoObj);
        $this->save();

        unlink($imagePath);

        return $photoObj;
    }

    //
    // Membership
    //

    public function isMember(?User $user): bool
    {
        return true;
    }

    public function getMembersModels(?User $user): array
    {
        return [];
    }

    public function addUser(?User $user): bool
    {
        return true;
    }

    public function toggleKick(?User $user, bool $kick = true): bool
    {
        return true;
    }

    public function toggleLeave(?User $user, bool $leave = true): bool
    {
        return true;
    }

    //
    // ACL
    //

    public function isCreator(?User $user): bool
    {
        return true;
    }

    public function canInviteUser(?User $user): bool
    {
        return true;
    }

    public function canChangePhoto(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->isCreator($user);
    }

    public function canAttachToTopic(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $topicsWith = DatabaseConnection::i()->getContext()->table("topics")->where(["deleted" => 0, "chat_id" => $this->getId()])->count();
        if ($topicsWith > 0) {
            return false;
        }

        return $this->isCreator($user);
    }

    //
    // Invitations
    //

    public function isApprovementsModeSet(): bool
    {
        return false;
    }

    public function decideApprovement(bool $approve = true): bool
    {
        return true;
    }

    public function getInvitationLinks(): array
    {
        return [];
    }

    public function createInvitationLink(): bool
    {
        return true;
    }

    public function removeInvitationLink(): bool
    {
        return true;
    }

    //
    // Serialization
    //

    public function toVkApiStruct(?User $user, ?array $a_data = null, ?array $acl = null): array
    {
        $photo = $this->getPhoto();

        bdump($a_data);
        $payload = [];
        $payload["type"] = "chat";

        if ($a_data != null) {
            $payload["admin_id"] = $a_data["admin_id"];
            $payload["left"] = $a_data["left"] ?? 0;
            $payload["kicked"] = $a_data["kicked"] ?? 0;
        }

        $payload["title"] = $this->getTitle();
        $payload["description"] = $this->getDescription();
        $payload["id"] = $this->getChatGlobalId();
        $payload["local_id"] = $this->getChatId();

        if ($photo != null) {
            $payload["photo_50"] = $photo->getURLBySizeId("miniscule");
            $payload["photo_100"] = $photo->getURLBySizeId("tiny");
            $payload["photo_200"] = $photo->getURLBySizeId("normal");
            $payload["avatar_max"] = $photo->getURLBySizeId("larger");
        } else {
            $payload["avatar_max"] = $payload["photo_200"] = $payload["photo_100"] = $payload["photo_50"] = "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";
        }

        $payload["users"] = [];
        $payload["push_settings"] = [
            "sound" => 1,
            "disabled_until" => null
        ];

        return $payload;
    }
}
