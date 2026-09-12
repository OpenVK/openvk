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
        if ($data == null || empty($data["response"]["items"])) {
            return;
        }

        $conv = $data["response"]["items"][0]["conversation"] ?? [];
        $chatSettings = $conv["chat_settings"] ?? [];
        $chatInfo = $data["response"]["chats"][0] ?? [];
        if (!is_array($chatInfo)) {
            $chatInfo = [];
        }
        $this->hydratedData = array_merge($chatInfo, is_array($conv) ? $conv : [], is_array($chatSettings) ? $chatSettings : []);
    }

    public function setData(array $data)
    {
        if (isset($data["chat_settings"]) && is_array($data["chat_settings"])) {
            $this->hydratedData = array_merge($data, $data["chat_settings"]);
        } else {
            $this->hydratedData = $data;
        }
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
        $history = $this->getAvatarsHistory(true);
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
        $history = $this->getAvatarsHistory(true);
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
        try {
            $raw = $this->getRecord()->photos_history;
        } catch (\Throwable $e) {
            $raw = null;
        }

        if (empty($raw)) {
            $photo = $this->getPhoto();
            if ($photo) {
                return $ids_only ? [(int) $photo->getId()] : [$photo];
            }
            return [];
        }

        if ($ids_only == true) {
            return array_map("intval", explode(",", $raw));
        }

        return (new Photos)->getByIds(explode(",", $raw));
    }

    public function getPhotoHistory(bool $ids_only = false): array
    {
        return $this->getAvatarsHistory($ids_only);
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
        if ($user === null) {
            return false;
        }

        $userId = (int) $user->getId();
        $userRealId = (int) $user->getRealId();

        if ($this->hasData()) {
            if (!empty($this->hydratedData["left"]) || !empty($this->hydratedData["kicked"])) {
                return false;
            }

            $state = $this->hydratedData["state"] ?? ($this->hydratedData["chat_settings"]["state"] ?? null);
            if ($state === "left" || $state === "kicked") {
                return false;
            }

            if (isset($this->hydratedData["can_write"]["reason"])) {
                $reason = (int) $this->hydratedData["can_write"]["reason"];
                if ($reason === 915 || $reason === 916) {
                    return false;
                }
            }

            $members = $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? ($this->hydratedData["chat_settings"]["members"] ?? ($this->hydratedData["chat_settings"]["users"] ?? null));
            if (is_array($members) && !empty($members)) {
                $memberIds = array_map("intval", $members);
                if (!in_array($userId, $memberIds, true) && !in_array($userRealId, $memberIds, true)) {
                    return false;
                }
            }

            return true;
        }

        $this->loadData($user);
        if ($this->hasData()) {
            return $this->isMember($user);
        }

        return false;
    }

    public function isKicked(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->hasData()) {
            if (!empty($this->hydratedData["kicked"])) {
                return true;
            }
            $state = $this->hydratedData["state"] ?? ($this->hydratedData["chat_settings"]["state"] ?? null);
            if ($state === "kicked") {
                return true;
            }
            if (isset($this->hydratedData["can_write"]["reason"]) && (int) $this->hydratedData["can_write"]["reason"] === 915) {
                return true;
            }
        }
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

    public function getOwnerId(): int
    {
        return (int) ($this->hydratedData["owner_id"] ?? $this->hydratedData["admin_id"] ?? 0);
    }

    public function getAdminIds(): array
    {
        if (isset($this->hydratedData["admin_ids"]) && is_array($this->hydratedData["admin_ids"])) {
            return array_values(array_unique(array_map("intval", $this->hydratedData["admin_ids"])));
        }
        $adminId = (int) ($this->hydratedData["admin_id"] ?? 0);
        return $adminId > 0 ? [$adminId] : [];
    }

    public function getPermissions(): array
    {
        return array_merge(self::getDefaultPermissions(), $this->hydratedData["permissions"] ?? []);
    }

    public function isCreator(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $ownerId = $this->getOwnerId();
        if ($ownerId === 0) {
            return true;
        }
        return ($user->getRealId() === $ownerId || $user->getId() === $ownerId);
    }

    public function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($this->isCreator($user)) {
            return true;
        }
        $adminIds = $this->getAdminIds();
        return in_array($user->getRealId(), $adminIds, true) || in_array($user->getId(), $adminIds, true);
    }

    public function canInviteUser(?User $user): bool
    {
        if (!$user || !$this->isMember($user)) {
            return false;
        }
        $perms = array_merge(self::getDefaultPermissions(), $this->hydratedData["permissions"] ?? []);
        return self::checkPermissionLevel($perms["invite"] ?? "all", $this->isCreator($user), $this->isAdmin($user), true);
    }

    public function canChangePhoto(?User $user): bool
    {
        if (!$user || !$this->isMember($user)) {
            return false;
        }
        $perms = array_merge(self::getDefaultPermissions(), $this->hydratedData["permissions"] ?? []);
        return self::checkPermissionLevel($perms["change_info"] ?? "admin", $this->isCreator($user), $this->isAdmin($user), true);
    }

    public function canModerate(?User $user): bool
    {
        if (!$user || !$this->isMember($user)) {
            return false;
        }
        return $this->isAdmin($user);
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

    public static function getDefaultPermissions(): array
    {
        return [
            "invite"             => "all",
            "change_info"        => "admin",
            "change_pin"         => "admin",
            "use_mass_mentions"  => "all",
            "see_invite_link"    => "admin",
            "change_invite_link" => "owner",
            "call"               => "all",
            "change_admins"      => "owner",
        ];
    }

    public static function checkPermissionLevel(string $level, bool $isOwner, bool $isAdmin, bool $isMember): bool
    {
        if (!$isMember) {
            return false;
        }
        if ($isOwner) {
            return true;
        }
        switch ($level) {
            case "all":
                return true;
            case "admin":
                return $isAdmin;
            case "owner":
                return $isOwner;
            default:
                return $isAdmin;
        }
    }

    public function computeAcl(array $permissions, bool $isOwner, bool $isAdmin, bool $isMember): array
    {
        return [
            "can_invite"             => self::checkPermissionLevel($permissions["invite"] ?? "all", $isOwner, $isAdmin, $isMember),
            "can_change_info"        => self::checkPermissionLevel($permissions["change_info"] ?? "admin", $isOwner, $isAdmin, $isMember),
            "can_change_pin"         => self::checkPermissionLevel($permissions["change_pin"] ?? "admin", $isOwner, $isAdmin, $isMember),
            "can_promote_users"      => self::checkPermissionLevel($permissions["change_admins"] ?? "owner", $isOwner, $isAdmin, $isMember),
            "can_see_invite_link"    => self::checkPermissionLevel($permissions["see_invite_link"] ?? "admin", $isOwner, $isAdmin, $isMember),
            "can_change_invite_link" => self::checkPermissionLevel($permissions["change_invite_link"] ?? "owner", $isOwner, $isAdmin, $isMember),
            "can_moderate"           => $isMember && ($isOwner || $isAdmin),
            "can_copy_chat"          => $isMember && ($isOwner || $isAdmin),
            "can_call"               => self::checkPermissionLevel($permissions["call"] ?? "all", $isOwner, $isAdmin, $isMember),
            "can_use_mass_mentions"  => self::checkPermissionLevel($permissions["use_mass_mentions"] ?? "all", $isOwner, $isAdmin, $isMember),
        ];
    }

    public static function isDefaultTitle(string $title): bool
    {
        $t = trim($title);
        if (preg_match('/^(Chat|Беседа|Чат|Conversation|Бесіда|Гутарка|Gespräch|Conversación)\s+\d+$/ui', $t)) {
            return true;
        }
        return false;
    }

    public function getDefaultTitle(): string
    {
        $prefix = tr("chat");
        if (empty($prefix) || str_starts_with($prefix, "@")) {
            $prefix = "Chat";
        }
        return "$prefix " . $this->getChatId();
    }

    private function resolveChatTitle(int $currentUserId): string
    {
        $customTitle = trim($this->getTitle());
        if (!empty($customTitle) && !self::isDefaultTitle($customTitle)) {
            return $customTitle;
        }

        if (!empty($this->hydratedData["title"])) {
            $hydratedTitle = trim($this->hydratedData["title"]);
            if (!empty($hydratedTitle) && !self::isDefaultTitle($hydratedTitle)) {
                return $hydratedTitle;
            }
        }

        $memberIds = $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? null;

        if ($memberIds == null) {
            return $this->getDefaultTitle();
        }

        $otherMemberIds = array_values(array_filter($memberIds, fn($id) => (int)$id !== (int)$currentUserId));

        if (!empty($otherMemberIds)) {
            $usersRepo = new Users();
            $selfName = tr("chat_title_self");
            if (empty($selfName) || str_starts_with($selfName, "@")) {
                $selfName = "Вы";
            }
            $names = [$selfName];
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
                    $single = tr("chat_title_construction_single");
                    return (!empty($single) && !str_starts_with($single, "@")) ? $single : ("Чат с вами");
                case 2:
                    $dialog = tr("chat_title_construction_dialogish", $names[1]);
                    return (!empty($dialog) && !str_starts_with($dialog, "@")) ? $dialog : ("Чат с вами и " . $names[1]);
                default:
                    $lastName = end($names);
                    if (sizeof($names) == 3) {
                        $c3 = tr("chat_title_construction", implode(', ', array_slice($names, 0, -1)), $lastName);
                        return (!empty($c3) && !str_starts_with($c3, "@")) ? $c3 : (implode(', ', array_slice($names, 0, -1)) . " и " . $lastName);
                    }

                    if (sizeof($names) > 3) {
                        $cMany = tr("chat_title_construction_many", implode(', ', array_slice($names, 0, 3)));
                        return (!empty($cMany) && !str_starts_with($cMany, "@")) ? $cMany : (implode(', ', array_slice($names, 0, 3)) . " и другие");
                    }
            }
        }

        return $this->getDefaultTitle();
    }

    public function toVkApiStruct(?User $user): array
    {
        $photo = $this->getPhoto();
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $userRealId = $user ? $user->getRealId() : 0;
        $userId = $user ? $user->getId() : 0;

        $ownerId = $this->getOwnerId();
        $adminIds = $this->getAdminIds();
        $adminId = (int) ($this->hydratedData["admin_id"] ?? ($ownerId > 0 ? $ownerId : ($adminIds[0] ?? 0)));

        $isOwner = ($userRealId > 0 && ($userRealId === $ownerId || $userId === $ownerId));
        $isAdmin = $isOwner || in_array($userRealId, $adminIds, true) || in_array($userId, $adminIds, true) || ($adminId === $userRealId && $userRealId > 0);

        $isMember = $this->isMember($user);

        $payload = [];
        $payload["type"] = "chat";

        if ($this->hasData()) {
            $payload["admin_id"] = $adminId;
            $payload["owner_id"] = $ownerId;
            $payload["admin_ids"] = $adminIds;
            if (!empty($this->hydratedData["left"]) || (!$isMember && empty($this->hydratedData["kicked"]))) {
                $payload["left"] = 1;
            }
            if (!empty($this->hydratedData["kicked"])) {
                $payload["kicked"] = 1;
            }
        } elseif (!$isMember) {
            $payload["left"] = 1;
        }

        $payload["title"] = $this->resolveChatTitle($userId);
        $payload["description"] = $this->getDescription();
        $payload["id"] = $this->getChatId();
        $payload["local_id"] = $this->getChatId();

        if ($isMember && $photo != null) {
            $payload["photo_50"] = $photo->getURLBySizeId("miniscule");
            $payload["photo_100"] = $photo->getURLBySizeId("tiny");
            $payload["photo_200"] = $photo->getURLBySizeId("normal");
            $payload["avatar_max"] = $photo->getURLBySizeId("larger");
        } elseif ($isMember) {
            $payload["avatar_max"] = $payload["photo_200"] = $payload["photo_100"] = $payload["photo_50"] = $server_url . "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";
        } else {
            $payload["avatar_max"] = $payload["photo_200"] = $payload["photo_100"] = $payload["photo_50"] = "";
        }

        $rawMembers = array_map("intval", $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? []);
        $members = $isMember ? $rawMembers : [];
        $payload["users"] = $members;
        if (!empty($members)) {
            $payload["members"] = $members;
            $payload["members_count"] = sizeof($members);
        } else {
            $payload["members_count"] = sizeof($rawMembers);
        }
        $payload["push_settings"] = [
            "sound" => 1,
            "disabled_until" => 0,
        ];

        $permissions = array_merge(self::getDefaultPermissions(), $this->hydratedData["permissions"] ?? []);
        $calculatedAcl = $this->computeAcl($permissions, $isOwner, $isAdmin, $isMember);
        $payload["acl"] = array_merge($calculatedAcl, $this->hydratedData['acl'] ?? []);
        $payload["permissions"] = $permissions;

        return $payload;
    }

    public function toChatSettingsStruct(?User $user): array
    {
        $isMember = $this->isMember($user);
        $struct = $this->toVkApiStruct($user);

        $photo = $this->getPhoto();
        $photoObj = null;
        if ($isMember && $photo != null) {
            $photoObj = [
                "photo_50"  => $photo->getURLBySizeId("miniscule"),
                "photo_100" => $photo->getURLBySizeId("tiny"),
                "photo_200" => $photo->getURLBySizeId("normal"),
            ];
        }

        $rawMembers = array_map("intval", $this->hydratedData["members"] ?? $this->hydratedData["users"] ?? []);
        $state = $this->hydratedData["state"] ?? ($isMember ? "in" : "left");
        if (!empty($this->hydratedData["left"])) {
            $state = "left";
        } elseif (!empty($this->hydratedData["kicked"])) {
            $state = "kicked";
        } elseif (!$isMember) {
            $state = "left";
        }

        $members = $isMember ? $rawMembers : [];

        $chatSettings = [
            "title"         => $struct["title"] ?? $this->getDefaultTitle(),
            "members_count" => count($rawMembers),
            "state"         => $state,
            "owner_id"      => (int) ($struct["owner_id"] ?? 0),
            "admin_id"      => (int) ($struct["admin_id"] ?? 0),
            "admin_ids"     => $struct["admin_ids"] ?? [],
            "active_ids"    => $isMember ? array_slice($rawMembers, 0, 10) : [],
            "members"       => $members,
            "users"         => $members,
            "photo_50"      => $isMember ? ($struct["photo_50"] ?? "") : "",
            "photo_100"     => $isMember ? ($struct["photo_100"] ?? "") : "",
            "photo_200"     => $isMember ? ($struct["photo_200"] ?? "") : "",
            "avatar_max"    => $isMember ? ($struct["avatar_max"] ?? "") : "",
            "acl"           => $struct["acl"] ?? [],
            "permissions"   => $struct["permissions"] ?? self::getDefaultPermissions(),
            "is_group_channel" => false,
            "is_service"    => false,
            "is_disappearing" => false,
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
