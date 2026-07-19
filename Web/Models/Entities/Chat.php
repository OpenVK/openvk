<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Photo};
use openvk\Web\Models\Repositories\Photos;
use PhpCsFixer\ConfigurationException\RequiredFixerConfigurationException;

class Chat extends RowModel
{
    protected $tableName = "chats";

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
        return true;
    }

    public function removePhotoFromHistory(?Photo $photo = null): bool
    {
        return true;
    }

    public function getPhotoHistory(): array
    {
        # TODO: return photos that was
        return [];
    }

    public function deleteCurrentPhoto(): bool
    {
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
        $photoObj->setUnlisted(1);
        $photoObj->setSystem(1);
        $photoObj->setFile([
            "tmp_name" => $imagePath,
            "error"    => 0,
        ]);
        $photoObj->save();

        $this->stateChanges("photo_id", $photoObj->getId());
        $this->pushPhotoToHistory($photoObj);

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

        if ($this->isCreator($user)) {
            return true;
        }

        return false;
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
        }

        $payload["users"] = [];
        $payload["push_settings"] = [
            "sound" => 1,
            "disabled_until" => null
        ];

        return $payload;
    }
}
