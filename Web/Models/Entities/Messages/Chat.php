<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Messages;

use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\{User, Photo};
use openvk\Web\Models\Repositories\{Users, Photos};
use PhpCsFixer\ConfigurationException\RequiredFixerConfigurationException;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Util\IMBroker;

class Chat extends RowModel
{
    protected $tableName = "chats";
    protected $hydratedData = null;

    public function hasData(): bool
    {
        return $this->hydratedData != null;
    }

    public function loadData(User $user): void
    {
        $broker = IMBroker::i();
        $response = $broker->invokeMethod($user->getId(), "messages.getConversationsById", [
            "peer_ids" => $this->getChatGlobalId(),
            "extended" => 1,
        ]);

        if ($response == false) {
            return;
        }

        $data = json_decode($response, true);
        if ($data == null || $data["response"] == null) {
            return;
        }

        $this->hydratedData = $data["response"]["items"][0]["conversation"];
    }

    public function setData(array $data)
    {
        $this->hydratedData = $data;
    }

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
        return $this->getRecord()->title;
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
            return "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";
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

    public function join(array $users): bool
    {
        $broker = new IMBroker();
        $joined = false;

        foreach ($users as $user) {
            #$response = $broker->invokeMethod($user->getRealId(), "messages.createChat", [
            #    "title"    => $title,
            #    "user_ids" => "",
            #]);
        }

        return $joined;
    }

    public function isMember(?User $user): bool
    {
        return true;
    }

    public function isKicked(?User $user): bool
    {
        return false;
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

    public function canJoin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return !$this->isKicked($user);
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

    public function getMembersCount(): int
    {
        if (!$this->hydratedData) {
            return 0;
        }

        return sizeof($this->hydratedData["members"]);
    }

    //
    // Serialization
    //

    private function resolveChatTitle(int $currentUserId): string
    {
        $customTitle = trim($this->getTitle());
        if (!empty($customTitle) && !str_starts_with($customTitle, "Chat ")) {
            return $customTitle;
        }

        if (!empty($this->hydratedData["title"])) {
            $hydratedTitle = trim($this->hydratedData["title"]);
            if (!empty($hydratedTitle) && !str_starts_with($hydratedTitle, "Chat ")) {
                return $hydratedTitle;
            }
        }

        $memberIds = $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? null;

        if ($memberIds == null) {
            return !empty($customTitle) ? $customTitle : ("Chat " . $this->getChatId());
        }

        $otherMemberIds = array_values(array_filter($memberIds, fn($id) => (int)$id !== (int)$currentUserId));

        if (!empty($otherMemberIds)) {
            $usersRepo = new Users();
            $names = [tr("chat_title_self")];
            foreach (array_slice(array_unique($otherMemberIds), 0, 10) as $id) {
                $u = $usersRepo->get($id);
                if ($u) {
                    $fn = $u->getFirstName();
                    if (!empty($fn)) {
                        $names[] = $fn;
                    }
                }
            }

            switch (sizeof($names)) {
                case 0:
                    return "0_o";
                case 1:
                    return tr("chat_title_construction_single");
                case 2:
                    return tr("chat_title_construction_dialogish", $names[1]);
                default:
                    $lastName = end($names);
                    if (sizeof($names) == 3) {
                        return tr("chat_title_construction", implode(', ', array_slice($names, 0, -1)), $lastName);
                    }

                    if (sizeof($names) > 3) {
                        return tr("chat_title_construction_many", implode(', ', array_slice($names, 0, 3)));
                    }
            }
        }

        return !empty($customTitle) ? $customTitle : ("Chat " . $this->getChatId());
    }

    public function toVkApiStruct(?User $user): array
    {
        $photo = $this->getPhoto();
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $userRealId = $user ? $user->getRealId() : 0;
        $userId = $user ? $user->getId() : 0;
        $isAdmin = (($this->hydratedData["admin_id"] ?? null) === $userRealId && $userRealId > 0);

        $payload = [];
        $payload["type"] = "chat";

        if ($this->hasData()) {
            $payload["admin_id"] = (int) ($this->hydratedData["admin_id"] ?? 0);
            if (!empty($this->hydratedData["left"])) {
                $payload["left"] = 1;
            }
            if (!empty($this->hydratedData["kicked"])) {
                $payload["kicked"] = 1;
            }
        }

        $payload["title"] = $this->resolveChatTitle($userId);
        $payload["description"] = $this->getDescription();
        $payload["id"] = $this->getChatId();
        $payload["local_id"] = $this->getChatId();

        if ($photo != null) {
            $payload["photo_50"] = $photo->getURLBySizeId("miniscule");
            $payload["photo_100"] = $photo->getURLBySizeId("tiny");
            $payload["photo_200"] = $photo->getURLBySizeId("normal");
            $payload["avatar_max"] = $photo->getURLBySizeId("larger");
        } else {
            $payload["avatar_max"] = $payload["photo_200"] = $payload["photo_100"] = $payload["photo_50"] = $server_url . "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";
        }

        $members = array_map("intval", $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? []);
        $payload["users"] = $members;
        if (!empty($members)) {
            $payload["members"] = $members;
            $payload["members_count"] = sizeof($members);
        }
        $payload["push_settings"] = [
            "sound" => 1,
            "disabled_until" => 0,
        ];

        $defaultAcl = [
            "can_invite"             => true,
            "can_change_info"        => $isAdmin,
            "can_change_pin"         => $isAdmin,
            "can_promote_users"      => $isAdmin,
            "can_see_invite_link"    => $isAdmin,
            "can_change_invite_link" => $isAdmin,
            "can_moderate"           => $isAdmin,
            "can_copy_chat"          => $isAdmin,
        ];

        $this->hydratedData['acl'] = array_merge($defaultAcl, $this->hydratedData['acl'] ?? []);

        return $payload;
    }

    public function toChatSettingsStruct(?User $user): array
    {
        $struct = $this->toVkApiStruct($user);

        $photo = $this->getPhoto();
        $photoObj = null;
        if ($photo != null) {
            $photoObj = [
                "photo_50"  => $photo->getURLBySizeId("miniscule"),
                "photo_100" => $photo->getURLBySizeId("tiny"),
                "photo_200" => $photo->getURLBySizeId("normal"),
            ];
        }

        $members = array_map("intval", $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? []);
        $state = "in";
        if (!empty($this->hydratedData["left"])) {
            $state = "left";
        } elseif (!empty($this->hydratedData["kicked"])) {
            $state = "kicked";
        }

        $chatSettings = [
            "title"         => $struct["title"] ?? ("Chat " . $this->getChatId()),
            "members_count" => count($members),
            "state"         => $state,
            "admin_id"      => (int) ($this->hydratedData["admin_id"] ?? 0),
            "active_ids"    => array_slice($members, 0, 10),
            "members"       => $members,
            "users"         => $members,
            "photo_50"      => $struct["photo_50"] ?? "",
            "photo_100"     => $struct["photo_100"] ?? "",
            "photo_200"     => $struct["photo_200"] ?? "",
            "avatar_max"    => $struct["avatar_max"] ?? "",
        ];

        if ($photoObj !== null) {
            $chatSettings["photo"] = $photoObj;
        }

        if (!empty($this->hydratedData["pinned_message"])) {
            $chatSettings["pinned_message"] = $this->hydratedData["pinned_message"];
        }

        return $chatSettings;
    }
}
