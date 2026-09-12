<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Nette\InvalidStateException;
use Nette\Utils\ImageException;
use openvk\Web\Util\IMBroker;
use openvk\Web\Models\Repositories\{Reports, Topics as TopicsRepo, Users as USRRepo, Clubs as ClubRepo, Messages as MSGRepo, Chats as ChatRepo};
use openvk\Web\Models\Entities\{Report, Photo, Message, Club as ClubEnt, User as UserEnt};
use openvk\Web\Models\Entities\Messages\Chat;
use openvk\VKAPI\Handlers\{Users as APIUsers, Groups as APIClubs};
use openvk\VKAPI\Utils\Uploader;
use openvk\Web\Models\Entities\Relationships\Blacklist;

final class Messages extends VKAPIRequestHandler
{
    private IMBroker $broker;

    public function __construct(...$otherDeps)
    {
        parent::__construct(...$otherDeps);
        $this->broker = IMBroker::i();
    }

    protected function ensureBrokerActive(): void
    {
        if (!$this->broker->isEnabled()) {
            throw new \openvk\VKAPI\Exceptions\APIErrorException("IM Service is disabled");
        }
    }

    protected function resolveSender($group_id = 0): int
    {
        $sender_id = $this->getUser()->getId();
        if ($group_id > 0) {
            $club = (new ClubRepo())->get((int) $group_id);

            if (!$club) {
                $this->fail(100, "One of the parameters specified was missing or invalid: group_id -> club not found");
            }

            if ($club->isBanned()) {
                $this->fail(15, "Access denied: this community is blocked");
            }

            if (!$club->canBeModifiedBy($this->getUser())) {
                $this->fail(15, "Access denied: you are not an administrator of this community");
            }

            $sender_id = ((int) $club->getId()) * -1;
        }
        return $sender_id;
    }

    protected function resolvePeer(
        int $user_id = -1,
        int $peer_id = 0,
        int $chat_id = -1,
        string $domain = ""
    ): ?int {
        if (!empty($domain)) {
            $uRepo = new USRRepo();
            $cRepo = new ClubRepo();
            $peerObj = $uRepo->getByShortUrl($domain) ?: $cRepo->getByShortUrl($domain);

            if (!$peerObj) {
                return null;
            }

            $id = (int) $peerObj->getId();
            return ($peerObj instanceof ClubEnt) ? -$id : $id;
        }

        if ($chat_id > 0) {
            return 2000000000 + $chat_id;
        }

        if ($peer_id !== 0) {
            return $peer_id;
        }

        if ($user_id > 0) {
            return $user_id;
        }

        return null;
    }

    protected function checkPeerAvailability(int $peerId, int $groupId): void
    {
        $uRepo = new USRRepo();
        $cRepo = new ClubRepo();
        $senderObj = null;
        $peer = null;

        if ($peerId > 0 && $peerId < 2000000000) {
            $peer = $uRepo->get($peerId);
        } elseif ($peerId < 0) {
            $peer = $cRepo->get(abs($peerId));
        }

        if (!$peer && $peerId < 2000000000) {
            $this->fail(936, "There is no peer with this id");
        }

        if (is_object($peer)) {
            if (method_exists($peer, 'isBanned') && $peer->isBanned()) {
                $this->fail(18, "Recipient is banned");
            }
            if (method_exists($peer, 'isDeleted') && $peer->isDeleted()) {
                $this->fail(18, "Recipient was deleted");
            }

            // Forbid users to write to groups that banned them

            $senderId = $this->resolveSender($groupId);
            if ($senderId > 0) {
                $senderObj = $uRepo->get($senderId);

                if ($senderObj) {
                    if (method_exists($peer, 'canWriteMessage')) {
                        if (!$peer->canWriteMessage($senderObj)) {
                            $this->fail(902, "Can't send messages to this user");
                        }
                    }
                }
            } else {
                $senderObj = $cRepo->get(abs($senderId));
            }

            if ($peerId > 0 && $peerId < 2000000000 && $senderId !== $peerId) {
                if ($senderObj instanceof \openvk\Web\Models\Entities\User) {
                    if ($peer->isBlacklistedBy($senderObj) || $senderObj->isBlacklistedBy($peer)) {
                        $this->fail(900, "Can't send messages for users from blacklist");
                    }
                }

                if ($senderId > 0 && method_exists($peer, 'getPrivacyPermission')) {
                    if (!$peer->getPrivacyPermission('messages.write', $senderObj)) {
                        $existence = $this->invoke("im.checkPeerExist", [
                            "peer_id" => $peerId
                        ]);

                        if (!$existence["exists"]) {
                            $this->fail(901, "Can't send messages by user privacy settings");
                        }
                    }

                    $relation = $peer->getSubscriptionStatus($this->getUser());

                    if (($relation == 0 || $relation == 1) && \openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->getUser(), "messages.notfriends")) {
                        $this->failTooOften("Limit exceed");
                    }
                }
            }
        }
    }

    private function enrichConversationCanWrite(array &$conversation, int $currentUserId): void
    {
        $peer = $conversation['peer'] ?? null;
        if (!$peer) {
            $conversation['can_write'] = ['allowed' => true];
            return;
        }

        $pType = $peer['type'] ?? 'user';
        $pId = (int) ($peer['id'] ?? 0);

        if ($pType === 'user') {
            if ($pId === $currentUserId && $currentUserId > 0) {
                $conversation['can_write'] = ['allowed' => true];
            } else {
                $peerUser = (new USRRepo())->get($pId);
                if (!$peerUser || $peerUser->isDeleted() || $peerUser->isBanned()) {
                    $conversation['can_write'] = ['allowed' => false, 'reason' => 18];
                } elseif ($this->getUser() && ($peerUser->isBlacklistedBy($this->getUser()) || $this->getUser()->isBlacklistedBy($peerUser))) {
                    $conversation['can_write'] = ['allowed' => false, 'reason' => 900];
                } elseif ($this->getUser() && !$peerUser->getPrivacyPermission('messages.write', $this->getUser())) {
                    $conversation['can_write'] = ['allowed' => false, 'reason' => 901];
                } else {
                    $conversation['can_write'] = ['allowed' => true];
                }
            }
        } elseif ($pType === 'group') {
            $club = (new ClubRepo())->get(abs($pId));
            if (!$club || (method_exists($club, 'isDeleted') && $club->isDeleted()) || (method_exists($club, 'isBanned') && $club->isBanned())) {
                $conversation['can_write'] = ['allowed' => false, 'reason' => 18];
            } elseif ($this->getUser() && method_exists($club, 'canWriteMessage') && !$club->canWriteMessage($this->getUser())) {
                $conversation['can_write'] = ['allowed' => false, 'reason' => 902];
            } else {
                $conversation['can_write'] = ['allowed' => true];
            }
        } elseif ($pType === 'chat') {
            $settings = $conversation['chat_settings'] ?? [];
            $state = $settings['state'] ?? 'in';
            if ($state === 'kicked') {
                $conversation['can_write'] = ['allowed' => false, 'reason' => 915];
            } elseif ($state === 'left') {
                $conversation['can_write'] = ['allowed' => false, 'reason' => 916];
            } elseif (isset($conversation['can_write']['allowed']) && !$conversation['can_write']['allowed']) {
                // preserve reason from openvk-im
            } else {
                $conversation['can_write'] = ['allowed' => true];
            }
        } else {
            $conversation['can_write'] = ['allowed' => true];
        }
    }

    protected function invoke(string $method, array $params = [], int $group_id = 0, ?int $replaced_owner = null)
    {
        $this->ensureBrokerActive();

        $sender_id = $this->resolveSender($group_id);

        if ($replaced_owner != null) {
            $sender_id = $replaced_owner;
        }

        try {
            $response = $this->broker->invokeMethod($sender_id, $method, $params);

            if ($response === false) {
                $this->fail(950, "IM Server unreachable");
            }

            $data = json_decode($response, true);

            if (isset($data['error'])) {
                $this->fail(
                    $data['error']['error_code'] ?? 500,
                    $data['error']['error_msg'] ?? "IM Error"
                );
            }

            return $data['response'] ?? $data;
        } catch (\Exception $e) {
            $this->fail(500, "Broker failure: " . $e->getMessage());
        }
    }

    protected function replaceAttachments(&$attachments, array $allowedAdditional = [])
    {
        if (empty($attachments)) {
            $attachments = [];
            return;
        }

        if (is_string($attachments)) {
            $decoded = json_decode($attachments, true);
            if (is_array($decoded)) {
                $attachments = $decoded;
            } else {
                $attachments = explode(',', $attachments);
            }
        }

        if (!is_array($attachments)) {
            $attachments = [$attachments];
        }

        $strAttachments = [];
        $objAttachments = [];

        foreach ($attachments as $att) {
            if (is_array($att) && !empty($att['type'])) {
                $objAttachments[] = $att;
            } elseif (is_object($att) && !empty($att->type)) {
                $objAttachments[] = $att;
            } elseif (is_string($att) && !empty($att)) {
                foreach (explode(',', $att) as $sub) {
                    $sub = trim($sub);
                    if ($sub !== '') {
                        $strAttachments[] = $sub;
                    }
                }
            }
        }

        $result = [];
        if (!empty($strAttachments)) {
            $parsed = parseAttachments($strAttachments, array_merge(['photo', 'video', 'audio', 'doc', 'poll', 'wall', 'sticker'], $allowedAdditional));

            foreach ($parsed as $attachment) {
                if (!$attachment) {
                    $result[] = [
                        "type"    => "unknown",
                        "unknown" => []
                    ];

                    continue;
                }

                if (!$attachment->canBeViewedBy($this->getUser())) {
                    $result[] = [
                        "type"    => $attachment->shortName,
                        $attachment->shortName => []
                    ];

                    continue;
                }

                $result[] = $attachment->toApiAttachment($this->getUser());
            }
        }

        $attachments = array_merge($result, $objAttachments);
    }

    private function sanitizeAttachmentDimensions(&$item): void
    {
        if (is_array($item)) {
            foreach ($item as $k => &$v) {
                if (($k === 'width' || $k === 'height') && is_null($v)) {
                    $v = 0;
                } elseif (is_array($v) || is_object($v)) {
                    $this->sanitizeAttachmentDimensions($v);
                }
            }
        } elseif (is_object($item)) {
            foreach ($item as $k => &$v) {
                if (($k === 'width' || $k === 'height') && is_null($v)) {
                    $item->{$k} = 0;
                } elseif (is_array($v) || is_object($v)) {
                    $this->sanitizeAttachmentDimensions($v);
                }
            }
        }
    }

    private function trSafe(string $key, ...$args): string
    {
        $res = tr($key, ...$args);
        if ($res === "@" . $key || $res === "@" . $key . "_other") {
            return "";
        }
        return $res;
    }

    private function getDefaultChatTitle(int $chatId): string
    {
        $prefix = tr("chat");
        if (empty($prefix) || str_starts_with($prefix, "@")) {
            $prefix = "Chat";
        }
        return "$prefix $chatId";
    }

    private function formatChatActionText(array $message): ?string
    {
        $action = $message['action'] ?? null;
        $actionType = null;
        $actionMid = $message['action_mid'] ?? null;
        $actionText = $message['action_text'] ?? null;

        if (is_array($action)) {
            $actionType = $action['type'] ?? null;
            $actionMid  = $action['member_id'] ?? $actionMid;
            $actionText = $action['text'] ?? $actionText;
        } elseif (is_string($action)) {
            $actionType = $action;
        }

        if (empty($actionType)) {
            return null;
        }

        $fromId = (int) ($message['from_id'] ?? $message['user_id'] ?? 0);
        $sender = $fromId > 0 ? (new USRRepo())->get($fromId) : null;
        $gender = "neutral";
        if ($sender instanceof UserEnt) {
            if ($sender->isFemale()) {
                $gender = "female";
            } elseif (!$sender->isNeutral()) {
                $gender = "male";
            }
        }

        $targetName = null;
        if (!empty($actionMid)) {
            $targetUser = (new USRRepo())->get((int) $actionMid);
            if ($targetUser instanceof UserEnt) {
                $targetName = $targetUser->getCanonicalName();
            } else {
                $targetName = "id" . $actionMid;
            }
        }

        $title = !is_null($actionText) ? trim((string) $actionText) : "";

        switch ($actionType) {
            case "chat_create":
                if ($title !== "") {
                    $out = $this->trSafe("event_chat_creation_" . $gender, $title);
                } else {
                    $out = $this->trSafe("event_chat_creation_no_title_" . $gender);
                }
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_create_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_create_impersonal") ?: "Новый чат");

            case "chat_title_update":
                $out = $this->trSafe("event_chat_title_update_" . $gender, $title);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_title_update_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_title_update_impersonal") ?: "Название беседы обновлено");

            case "chat_photo_update":
                $out = $this->trSafe("event_chat_photo_update_" . $gender);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_photo_update_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_photo_update_impersonal") ?: "Фотография беседы обновлена");

            case "chat_photo_remove":
                $out = $this->trSafe("event_chat_photo_remove_" . $gender);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_photo_remove_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_photo_remove_impersonal") ?: "Фотография беседы удалена");

            case "chat_pin_message":
                $out = $this->trSafe("event_chat_pin_message_" . $gender);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_pin_message_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_pin_message_impersonal") ?: "Закреплено сообщение");

            case "chat_unpin_message":
                $out = $this->trSafe("event_chat_unpin_message_" . $gender);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_unpin_message_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_unpin_message_impersonal") ?: "Откреплено сообщение");

            case "chat_invite_user":
                if ($actionMid && (int) $actionMid === $fromId) {
                    $out = $this->trSafe("event_chat_invite_user_self_" . $gender);
                } else {
                    $out = $this->trSafe("event_chat_invite_user_" . $gender, $targetName ?? "");
                }
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_invite_user_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_invite_user_impersonal") ?: "Приглашён участник");

            case "chat_invite_user_by_link":
                $out = $this->trSafe("event_chat_invite_user_by_link_" . $gender);
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_invite_user_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_invite_user_impersonal") ?: "Присоединился к беседе по ссылке");

            case "chat_kick_user":
                if ($actionMid && (int) $actionMid === $fromId) {
                    $out = $this->trSafe("event_chat_kick_user_self_" . $gender);
                } else {
                    $out = $this->trSafe("event_chat_kick_user_" . $gender, $targetName ?? "");
                }
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_kick_user_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_kick_user_impersonal") ?: "Участник исключён");

            case "chat_moderator_add":
                $out = $this->trSafe("event_chat_moderator_add_" . $gender, $targetName ?? "");
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_moderator_add_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_moderator_add_impersonal") ?: "Назначен администратор");

            case "chat_moderator_remove":
                $out = $this->trSafe("event_chat_moderator_remove_" . $gender, $targetName ?? "");
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_moderator_remove_impersonal");
                }
                return !empty($out) ? $out : (tr("event_chat_moderator_remove_impersonal") ?: "Сняты полномочия администратора");

            case "rating_up":
                $senderName = $sender ? $sender->getCanonicalName() : "id" . $fromId;
                $out = $this->trSafe("event_chat_user_up_your_rating_" . $gender, $senderName, (string) ($actionMid ?? ""));
                if (empty($out)) {
                    $out = $this->trSafe("event_chat_rating_up_impersonal");
                }
                return !empty($out) ? $out : "Вам повысили рейтинг";

            case "coins_transfer":
                $senderName = $sender ? $sender->getCanonicalName() : "id" . $fromId;
                $out = $this->trSafe("event_chat_user_added_voices_" . $gender, $senderName, (string) ($actionMid ?? ""));
                if (empty($out)) {
                    $out = $this->trSafe("event_coins_transfer_impersonal");
                }
                return !empty($out) ? $out : "Вам внесли голоса";

            default:
                $out = $this->trSafe("event_" . $actionType . "_impersonal");
                if (!empty($out)) {
                    return $out;
                }
                return (string) ($message['body'] ?? $message['text'] ?? "");
        }
    }

    private function sanitizeMessageAttachmentsRecursive(array &$message): void
    {
        if (!empty($message['action'])) {
            $actionFormatted = $this->formatChatActionText($message);
            if (!empty($actionFormatted)) {
                $message['body'] = $actionFormatted;
                if (isset($message['text'])) {
                    $message['text'] = $actionFormatted;
                }
            }
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            if (!empty($message['reply_message']) && empty($message['fwd_messages'])) {
                $message['fwd_messages'] = [$message['reply_message']];
                unset($message['reply_message']);
            }
        }

        if (!empty($message['attachments'])) {
            $this->replaceAttachments($message['attachments'], ["gift"]);
            $this->sanitizeAttachmentDimensions($message['attachments']);
        } else {
            $message['attachments'] = [];
        }

        if (!empty($message['fwd_messages']) && (is_array($message['fwd_messages']) || is_object($message['fwd_messages']))) {
            $fwdArr = (array) $message['fwd_messages'];
            foreach ($fwdArr as &$fwd) {
                if (is_object($fwd)) {
                    $fwd = (array) $fwd;
                }
                if (is_array($fwd)) {
                    $fwdUid = (int) ($fwd['uid'] ?? $fwd['from_id'] ?? $fwd['user_id'] ?? 0);
                    $fwd['uid']        = $fwdUid;
                    $fwd['user_id']    = $fwdUid;
                    $fwd['from_id']    = $fwdUid;
                    $fwd['mid']        = (int) ($fwd['mid'] ?? $fwd['id'] ?? 0);
                    $fwd['id']         = $fwd['mid'];
                    $fwd['body']       = (string) ($fwd['body'] ?? $fwd['text'] ?? "");
                    $fwd['date']       = (int) ($fwd['date'] ?? 0);
                    $fwd['read_state'] = (int) ($fwd['read_state'] ?? 0);
                    $fwd['out']        = (int) ($fwd['out'] ?? 0);
                    $this->sanitizeMessageAttachmentsRecursive($fwd);
                }
            }
            unset($fwd);
            $message['fwd_messages'] = array_values($fwdArr);
        }

        if (!empty($message['reply_message']) && is_array($message['reply_message'])) {
            $this->sanitizeMessageAttachmentsRecursive($message['reply_message']);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            if (empty($message['attachments'])) {
                unset($message['attachments']);
            }
            if (empty($message['fwd_messages'])) {
                unset($message['fwd_messages']);
            }
        }
    }

    private function collectEntityIdsRecursive(array $message, array &$userIds, array &$groupIds): void
    {
        $senderId = (int) ($message['from_id'] ?? $message['user_id'] ?? 0);
        if ($senderId > 0 && $senderId < 2000000000) {
            $userIds[] = $senderId;
        } elseif ($senderId < 0) {
            $groupIds[] = abs($senderId);
        }

        if (!empty($message['reply_message']) && is_array($message['reply_message'])) {
            $this->collectEntityIdsRecursive($message['reply_message'], $userIds, $groupIds);
        }

        if (!empty($message['fwd_messages']) && is_array($message['fwd_messages'])) {
            foreach ($message['fwd_messages'] as $fwd) {
                if (is_array($fwd)) {
                    $this->collectEntityIdsRecursive($fwd, $userIds, $groupIds);
                }
            }
        }
    }

    /**
     * Преобразует расширенные данные (profiles, groups, chats) в полные структуры VK API
     *
     * @param array $payload Ссылка на данные от IM сервиса
     * @param string $fields Дополнительные поля для USRRepo
     */
    private function hydrateExtendedData(array &$payload, string $fields = "photo_200,online", ?array $loadedChats = []): void
    {
        $loadedChats = $loadedChats ?? [];
        if (!empty($payload['profiles'])) {
            $userIDs = array_map(fn($u) => is_array($u) ? ($u['id'] ?? 0) : (int) $u, $payload['profiles']);
            $userIDs = array_unique(array_filter($userIDs));

            $payload['profiles'] = !empty($userIDs)
                ? (new APIUsers())->get(implode(',', $userIDs), $fields)
                : [];
        } else {
            $payload['profiles'] = [];
        }

        if (!empty($payload['groups'])) {
            $groupIDs = array_map(fn($g) => abs(is_array($g) ? ($g['id'] ?? 0) : (int) $g), $payload['groups']);
            $groupIDs = array_unique(array_filter($groupIDs));

            $payload['groups'] = !empty($groupIDs)
                ? (new APIClubs())->getById(implode(',', $groupIDs), "", $fields)
                : [];
        } else {
            $payload['groups'] = [];
        }

        $extendedChats = [];
        if (!empty($payload['chats'])) {
            $chatsRepo = new ChatRepo();
            $currentUserId = $this->getUser() ? $this->getUser()->getId() : 0;

            foreach ($payload['chats'] as &$chat) {
                $idVal = is_array($chat) ? ($chat['id'] ?? 0) : (int) $chat;

                $globalChatId = abs($idVal);
                $localChatId = $globalChatId > 2000000000 ? ($globalChatId - 2000000000) : $globalChatId;

                if ($localChatId <= 0) {
                    continue;
                }

                $chatEntity = $loadedChats[$localChatId] ?? $chatsRepo->getByChatId($localChatId);
                if (!$chatEntity) {
                    $chatEntity = $chatsRepo->create($localChatId, $this->getDefaultChatTitle($localChatId));
                }

                if (!$chatEntity->hasData() && is_array($chat)) {
                    $chatEntity->setData($chat);
                }

                $extendedChats[] = $chatEntity->toVkApiStruct($this->getUser());
            }
        }

        $payload['chats'] = $extendedChats;
    }

    // ----------------------------------
    //             Longpoll
    // ----------------------------------

    public function getLongPollHistory(
        int $ts = -1,
        int $pts = -1,
        int $preview_length = 0,
        int $events_limit = 1000,
        int $msgs_limit = 1000,
        int $group_id = 0,
        int $lp_version = 2,
        string $fields = "photo_200,online",
        int $onlines = 0
    ): object {
        $this->requireUser();

        $params = [
            "events_limit" => (string) $events_limit,
            "msgs_limit"   => (string) $msgs_limit,
            "version"      => (string) $lp_version,
        ];

        if ($ts > 0) {
            $params["ts"] = (string) $ts;
        }
        if ($pts > 0) {
            $params["pts"] = (string) $pts;
        }
        if ($preview_length > 0) {
            $params["preview_length"] = (string) $preview_length;
        }
        if (!empty($fields)) {
            $params["fields"] = $fields;
        }

        $data = $this->invoke("messages.getLongPollHistory", $params, $group_id);

        if (!empty($data['messages']['items'])) {
            foreach ($data['messages']['items'] as &$msg) {
                $this->sanitizeMessageAttachmentsRecursive($msg);
            }
            unset($msg);
        }

        $this->hydrateExtendedData($data, $fields);

        return (object) $data;
    }

    public function getLongPollServer(int $need_pts = 0, int $lp_version = 2, int $use_ssl = 0, ?int $group_id = null): array
    {
        $this->requireUser();
        $baseUrl = $this->broker->getLongPollBaseUrl();

        if (!$this->broker->pingLP($baseUrl)) {
            $this->fail(500, "LongPoll server is unreachable. Check proxy settings for /nim endpoint.");
        }

        $params = [
            "version"  => (string) $lp_version,
            "need_pts" => (string) $need_pts,
        ];

        if ($group_id > 0) {
            $params['group_id'] = (string) $group_id;
        }

        $data = $this->invoke("messages.getLongPollServer", $params, (int) $group_id);
        $data['server'] = preg_replace('#^(https?:)?//#i', '', $baseUrl);

        $isLegacy = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && defined("VKAPI_DECL_VER_MINOR") && VKAPI_DECL_VER_MINOR < 80);
        if ($isLegacy && $need_pts === 0) {
            unset($data['pts']);
        }

        $data['unread_count'] = $this->getUser()->getUnreadMessagesCount();

        return $data;
    }

    // ----------------------------------
    //             Messages
    // ----------------------------------

    public function get(
        int $out = 0,
        int $offset = 0,
        int $count = 20,
        int $time_offset = 0,
        int $filters = 0,
        int $preview_length = 0,
        int $last_message_id = 0,
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ): array {
        $this->requireUser();

        $params = [
            "out"             => (string) $out,
            "offset"          => (string) $offset,
            "count"           => (string) min(abs($count), 200),
            "time_offset"     => (string) $time_offset,
            "filters"         => (string) $filters,
            "preview_length"  => (string) $preview_length,
            "last_message_id" => (string) $last_message_id,
            "extended"        => (string) $extended,
            "fields"          => $fields,
        ];

        $payload = $this->invoke("messages.get", $params, $group_id);
        $loadedChats = [];

        if (!empty($payload['items'])) {
            $chatIds = [];
            foreach ($payload['items'] as $m) {
                $cId = (int) ($m['chat_id'] ?? 0);
                if ($cId === 0 && !empty($m['peer_id']) && $m['peer_id'] > 2000000000) {
                    $cId = $m['peer_id'] - 2000000000;
                }
                if ($cId > 0) {
                    $chatIds[] = $cId;
                }
            }
            $chatIds = array_unique(array_filter($chatIds));
            if (!empty($chatIds)) {
                $chatsRepo = new ChatRepo();
                foreach ($chatIds as $cId) {
                    $chatObj = $chatsRepo->getByChatId($cId);
                    if ($chatObj) {
                        $loadedChats[$cId] = $chatObj;
                    }
                }
            }

            $currentUserId = $this->getUser()->getId();
            $formattedItems = [];

            foreach ($payload['items'] as $message) {
                $msgOut = (int) ($message['out'] ?? 0);
                $fromId = (int) ($message['from_id'] ?? $message['user_id'] ?? 0);
                $peerId = (int) ($message['peer_id'] ?? 0);
                $chatId = (int) ($message['chat_id'] ?? 0);

                if ($chatId === 0 && $peerId > 2000000000) {
                    $chatId = $peerId - 2000000000;
                }

                $userId = (int) ($message['user_id'] ?? 0);
                if ($userId === 0) {
                    if ($chatId > 0) {
                        $userId = $fromId ?: $currentUserId;
                    } else {
                        $userId = ($msgOut === 1) ? ($peerId ?: $fromId) : ($fromId ?: $peerId);
                    }
                }

                $text = (string) ($message['body'] ?? $message['text'] ?? "");
                $hasEmoji = (int) ($message['emoji'] ?? 0);
                if (!$hasEmoji && !empty($text)) {
                    $hasEmoji = (preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $text)) ? 1 : 0;
                }

                $isDeleted = !empty($message['deleted']) ? 1 : 0;

                $msgObj = [
                    "id"          => (int) ($message['id'] ?? 0),
                    "date"        => (int) ($message['date'] ?? 0),
                    "out"         => $msgOut,
                    "user_id"     => $userId,
                    "read_state"  => (int) ($message['read_state'] ?? 0),
                    "title"       => (string) ($message['title'] ?? ""),
                    "body"        => $isDeleted ? "" : $text,
                    "attachments" => $isDeleted ? [] : ($message['attachments'] ?? []),
                    "fwd_messages"=> $isDeleted ? [] : ($message['fwd_messages'] ?? []),
                    "emoji"       => $isDeleted ? 0 : $hasEmoji,
                    "deleted"     => $isDeleted,
                ];

                if (!empty($message['important'])) {
                    $msgObj['important'] = true;
                }

                if (!empty($message['action'])) {
                    $msgObj['action'] = $message['action'];
                    if (!empty($message['action_mid'])) {
                        $msgObj['action_mid'] = (int) $message['action_mid'];
                    }
                    if (!empty($message['action_text'])) {
                        $msgObj['action_text'] = (string) $message['action_text'];
                    }
                    if (!empty($message['action_email'])) {
                        $msgObj['action_email'] = (string) $message['action_email'];
                    }
                }

                if (!$isDeleted) {
                    $this->sanitizeMessageAttachmentsRecursive($msgObj);
                } elseif (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                    unset($msgObj['attachments'], $msgObj['fwd_messages']);
                }

                if ($chatId > 0) {
                    $msgObj['chat_id'] = $chatId;
                    $chatEntity = $loadedChats[$chatId] ?? null;

                    $rawActive = !empty($message['chat_active']) ? (array)$message['chat_active'] : [];
                    $rawCount = (int) ($message['users_count'] ?? count($rawActive));
                    $rawAdmin = (int) ($message['admin_id'] ?? 0);
                    $rawTitle = (string) ($message['title'] ?? "");

                    if ($chatEntity) {
                        $chatStruct = $chatEntity->toChatSettingsStruct($this->getUser());
                        $msgObj['title'] = !empty($rawTitle) ? $rawTitle : ($chatStruct['title'] ?? $this->getDefaultChatTitle($chatId));
                        $msgObj['admin_id'] = $rawAdmin ?: (int) ($chatStruct['admin_id'] ?? 0);
                        $msgObj['users_count'] = $rawCount ?: (int) ($chatStruct['members_count'] ?? 0);
                        $msgObj['chat_active'] = !empty($rawActive) ? $rawActive : ($chatStruct['active_ids'] ?? []);
                        $msgObj['photo_50'] = $chatStruct['photo_50'] ?? "";
                        $msgObj['photo_100'] = $chatStruct['photo_100'] ?? "";
                        $msgObj['photo_200'] = $chatStruct['photo_200'] ?? "";

                        $chatEntity->setData([
                            "title"      => $msgObj['title'],
                            "admin_id"   => $msgObj['admin_id'],
                            "members"    => $msgObj['chat_active'],
                            "users"      => $msgObj['chat_active'],
                            "photo_50"   => $msgObj['photo_50'],
                            "photo_100"  => $msgObj['photo_100'],
                            "photo_200"  => $msgObj['photo_200'],
                        ]);
                    } else {
                        $msgObj['title'] = !empty($rawTitle) ? $rawTitle : $this->getDefaultChatTitle($chatId);
                        $msgObj['admin_id'] = $rawAdmin;
                        $msgObj['users_count'] = $rawCount;
                        $msgObj['chat_active'] = $rawActive;
                    }
                }

                if ($preview_length > 0) {
                    $msgObj['body'] = ovk_truncate_words($msgObj['body'], $preview_length);
                }

                if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                    $msgObj['mid'] = $msgObj['id'];
                    $msgObj['uid'] = $msgObj['user_id'];
                    if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 4 && isset($msgObj['chat_active'])) {
                        $msgObj['chat_active'] = is_array($msgObj['chat_active'])
                            ? implode(',', array_filter($msgObj['chat_active']))
                            : (string) $msgObj['chat_active'];
                    }
                }

                $formattedItems[] = $msgObj;
            }

            $payload['items'] = $formattedItems;
        }

        if ($extended == 1) {
            $userIDs = [];
            $groupIDs = [];
            $chatIDs = [];

            if (!empty($payload['items'])) {
                foreach ($payload['items'] as $item) {
                    if (!empty($item['user_id'])) {
                        if ($item['user_id'] > 0 && $item['user_id'] < 2000000000) {
                            $userIDs[] = (int) $item['user_id'];
                        } elseif ($item['user_id'] < 0) {
                            $groupIDs[] = abs((int) $item['user_id']);
                        }
                    }
                    if (!empty($item['from_id'])) {
                        if ($item['from_id'] > 0 && $item['from_id'] < 2000000000) {
                            $userIDs[] = (int) $item['from_id'];
                        } elseif ($item['from_id'] < 0) {
                            $groupIDs[] = abs((int) $item['from_id']);
                        }
                    }
                    if (!empty($item['admin_id']) && $item['admin_id'] > 0) {
                        $userIDs[] = (int) $item['admin_id'];
                    }
                    if (!empty($item['chat_active'])) {
                        foreach ($item['chat_active'] as $uid) {
                            if ($uid > 0) {
                                $userIDs[] = (int) $uid;
                            }
                        }
                    }
                    if (!empty($item['chat_id'])) {
                        $chatIDs[] = (int) $item['chat_id'];
                    }
                    if (!empty($item['fwd_messages'])) {
                        $this->collectEntityIdsRecursive($item, $userIDs, $groupIDs);
                    }
                }
            }

            if (!empty($payload['profiles'])) {
                foreach ($payload['profiles'] as $p) {
                    $userIDs[] = is_array($p) ? ($p['id'] ?? 0) : (int) $p;
                }
            }
            if (!empty($payload['groups'])) {
                foreach ($payload['groups'] as $g) {
                    $groupIDs[] = abs(is_array($g) ? ($g['id'] ?? 0) : (int) $g);
                }
            }
            if (!empty($payload['chats'])) {
                foreach ($payload['chats'] as $c) {
                    $chatIDs[] = is_array($c) ? ($c['id'] ?? 0) : (int) $c;
                }
            }

            $payload['profiles'] = array_values(array_unique(array_filter($userIDs)));
            $payload['groups'] = array_values(array_unique(array_filter($groupIDs)));
            $payload['chats'] = array_values(array_unique(array_filter($chatIDs)));

            $this->hydrateExtendedData($payload, $fields, $loadedChats);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $items = $payload['items'] ?? [];
            $total = (int) ($payload['count'] ?? count($items));
            if ($total === 0 || empty($items)) {
                return [0];
            }
            return array_merge([$total], $items);
        }

        return $payload;
    }

    public function getById(string $message_ids = "", int $preview_length = 0, int $extended = 0, string $fields = "photo_200,online")
    {
        $this->requireUser();

        if ($message_ids === "") {
            $message_ids = (string) ($_POST['message_ids'] ?? $_GET['message_ids'] ?? $_POST['mid'] ?? $_GET['mid'] ?? $_POST['mids'] ?? $_GET['mids'] ?? "");
        }

        $params = [
            "message_ids"    => $message_ids,
            "extended"       => (string) $extended,
            "preview_length" => (string) $preview_length,
            "fields"         => $fields,
        ];

        $data = $this->invoke("messages.getById", $params);

        if (!empty($data['items'])) {
            $isLegacy = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && defined("VKAPI_DECL_VER_MINOR") && VKAPI_DECL_VER_MINOR < 80);
            $chatsRepo = new ChatRepo();
            $loadedChats = [];

            foreach ($data['items'] as &$item) {
                $this->sanitizeMessageAttachmentsRecursive($item);

                if ($isLegacy && !empty($item['chat_id'])) {
                    $cId = (int) $item['chat_id'];
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? $this->getDefaultChatTitle($cId);
                        }
                        if (empty($item['photo_50'])) {
                            $item['photo_50'] = $chatStruct['photo_50'] ?? "";
                            $item['photo_100'] = $chatStruct['photo_100'] ?? "";
                            $item['photo_200'] = $chatStruct['photo_200'] ?? "";
                        }
                    }
                }

                if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                    $mid = (int) ($item['mid'] ?? $item['id'] ?? 0);
                    $uid = (int) ($item['uid'] ?? $item['user_id'] ?? $item['from_id'] ?? 0);
                    $item['mid']        = $mid;
                    $item['id']         = $mid;
                    $item['uid']        = $uid;
                    $item['user_id']    = $uid;
                    $item['from_id']    = $uid;
                    $item['body']       = (string) ($item['body'] ?? $item['text'] ?? "");
                    $item['date']       = (int) ($item['date'] ?? 0);
                    $item['read_state'] = (int) ($item['read_state'] ?? 0);
                    $item['out']        = (int) ($item['out'] ?? 0);
                    if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 4 && isset($item['chat_active'])) {
                        $item['chat_active'] = is_array($item['chat_active'])
                            ? implode(',', array_filter($item['chat_active']))
                            : (string) $item['chat_active'];
                    }
                }
            }
            unset($item);
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($data, $fields);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $items = $data['items'] ?? [];
            $total = (int) ($data['count'] ?? count($items));
            if ($total === 0 || empty($items)) {
                return [0];
            }
            return array_merge([$total], $items);
        }

        return (object) $data;
    }

    public function send(
        int $user_id = -1,
        int $peer_id = 0,
        string $domain = "",
        int $chat_id = -1,
        int $group_id = 0,
        string $user_ids = "",
        string $peer_ids = "",
        string $message = "",
        int $sticker_id = -1,
        int $unnoticed = 0,
        string $attachment = "",
        int $random_id = 0,
        int $guid = 0,
        int $reply_to = 0,
        string $forward_messages = "",
        string $forward = "",
        ?float $lat = null,
        ?float $long = null
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if (empty($forward_messages)) {
            $forward_messages = (string) ($_POST['forward_messages'] ?? $_GET['forward_messages'] ?? $_POST['fwd_messages'] ?? $_GET['fwd_messages'] ?? '');
        }

        if (empty($forward)) {
            $forward = (string) ($_POST['forward'] ?? $_GET['forward'] ?? '');
        }

        if (!empty($forward) && empty($forward_messages)) {
            $decoded = json_decode($forward, true);
            if (is_array($decoded)) {
                if (!empty($decoded['conversation_message_ids']) && is_array($decoded['conversation_message_ids'])) {
                    $forward_messages = implode(',', $decoded['conversation_message_ids']);
                } elseif (!empty($decoded['message_ids']) && is_array($decoded['message_ids'])) {
                    $forward_messages = implode(',', $decoded['message_ids']);
                }
                if (!empty($decoded['is_reply']) && empty($reply_to)) {
                    $reply_to = (int) ($decoded['conversation_message_ids'][0] ?? $decoded['message_ids'][0] ?? 0);
                }
            }
        }

        if ($guid !== 0 && $random_id === 0) {
            $random_id = $guid;
        }

        $cleanMessage = trim(preg_replace('/[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+/u', ' ', $message));
        if ($cleanMessage === '') {
            $message = '';
        } else {
            $message = preg_replace('/^[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+|[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+$/u', '', $message);
        }

        if (empty($peer_ids)) {
            $peer_ids = (string) ($_POST['peer_ids'] ?? $_GET['peer_ids'] ?? '');
        }

        if (!empty($peer_ids)) {
            $pIds = preg_split("%, ?%", $peer_ids);
            if (count($pIds) > 25) {
                $this->fail(913, "Too many recipients");
            }

            $results = [];
            foreach ($pIds as $pIdStr) {
                $pId = (int) trim($pIdStr);
                if ($pId === 0) {
                    continue;
                }
                try {
                    $sentMid = (int) $this->send(-1, $pId, "", -1, $group_id, "", "", $message, $sticker_id, 1, $attachment, rand(1, 2147483647), 0, $reply_to, $forward_messages, $forward);
                    $results[] = [
                        "peer_id"                 => $pId,
                        "message_id"              => $sentMid,
                        "conversation_message_id" => $sentMid,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        "peer_id" => $pId,
                        "error"   => [
                            "code"        => $e->getCode() ?: 900,
                            "description" => $e->getMessage(),
                        ],
                    ];
                }
            }
            return $results;
        }

        if (!empty($user_ids)) {
            $ids = preg_split("%, ?%", $user_ids);
            if (count($ids) > 25) {
                $this->fail(913, "Too many recipients");
            }

            $rIds = [];
            foreach ($ids as $id) {
                $rIds[] = (int) $this->send(-1, (int) $id, "", -1, $group_id, "", "", $message, $sticker_id, 1, $attachment, rand(1, 2147483647), 0, $reply_to, $forward_messages, $forward);
            }
            return $rIds;
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);
        if (is_null($resolvedId) || $resolvedId === 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: no recipient");
        }

        $attachment_checked = parseAttachments($attachment, ["photo", "video", "doc", "audio", "wall", "sticker"]);
        $attachment_secure = [];
        $formatted_attachments = [];
        $stickerCount = 0;
        $otherAttachCount = 0;

        if ($sticker_id > 0) {
            $stk = (new \openvk\Web\Models\Repositories\Stickers())->getSticker($sticker_id);
            if (!$stk || $stk->isDeleted()) {
                $this->fail(100, "Sticker not found");
            }

            if (!$stk->canBeUsedBy($this->getUser())) {
                $this->fail(100, "Sticker is not available for you");
            }

            $stickerCount++;
            $attachment_secure[] = $stk->getAttachmentString();
            $formatted_attachments[] = $stk->toApiAttachment($this->getUser());
        }

        foreach ($attachment_checked as $item) {
            if (!$item || !$item->canBeViewedBy($this->getUser())) {
                continue;
            }

            if ($item instanceof \openvk\Web\Models\Entities\Messages\Sticker) {
                if (!$item->canBeUsedBy($this->getUser())) {
                    $this->fail(100, "Sticker is not available for you");
                }
                $stickerCount++;
            } else {
                $otherAttachCount++;
            }

            $attachment_secure[] = $item->getAttachmentString();
            $formatted_attachments[] = $item->toApiAttachment($this->getUser());
        }

        if ($stickerCount > 0) {
            if ($stickerCount > 1) {
                $this->fail(100, "Only one sticker can be sent per message");
            }

            if ($otherAttachCount > 0) {
                $this->fail(100, "Stickers cannot be sent with other attachments");
            }

            if (!empty($message)) {
                $this->fail(100, "Stickers cannot be sent with text");
            }

            if (!empty($forward_messages)) {
                $this->fail(100, "Stickers cannot be sent with forwarded messages");
            }
        }

        if (empty($message) && count($attachment_secure) === 0 && empty($forward_messages) && $reply_to <= 0) {
            $this->fail(100, "Message text is empty or invalid");
        }

        if ($unnoticed == 0) {
            $this->getUser()->updOnline($this->getPlatform());
        }

        $this->checkPeerAvailability($resolvedId, $group_id);

        $params = [
            "peer_id"    => (string) $resolvedId,
            "message"    => $message,
            "attachment" => implode(",", $attachment_secure),
            "random_id"  => (string) ($random_id ?: rand(1, 2147483647)),
            "guid"       => (string) ($guid ?: $random_id),
        ];

        if (!empty($formatted_attachments)) {
            $params["attachments_json"] = json_encode($formatted_attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($user_id > 0) {
            $params["user_id"] = (string) $user_id;
        }
        if ($chat_id > 0) {
            $params["chat_id"] = (string) $chat_id;
        }
        if ($reply_to > 0) {
            $params["reply_to"] = (string) $reply_to;
        }
        if (!empty($forward_messages)) {
            $params["forward_messages"] = $forward_messages;
        }
        if ($lat !== null) {
            $params["lat"] = (string) $lat;
        }
        if ($long !== null) {
            $params["long"] = (string) $long;
        }

        return (int) $this->invoke("messages.send", $params, $group_id);
    }

    public function edit(
        int $peer_id = 0,
        int $message_id = 0,
        string $message = "",
        string $attachment = "",
        int $keep_forward_messages = 0,
        int $group_id = 0,
        string $domain = "",
        int $user_id = -1,
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($message_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_id is required");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);
        if (is_null($resolvedId) || $resolvedId === 0) {
            $this->fail(936, "There is no peer with this id");
        }

        // Forbid editing messages that have a sticker
        $msgData = $this->invoke("messages.getById", [
            "message_ids" => (string) $message_id,
        ]);
        if (!empty($msgData['items'][0])) {
            $msgItem = $msgData['items'][0];
            $hasExistingSticker = false;
            if (!empty($msgItem['attachments'])) {
                foreach ((array) $msgItem['attachments'] as $att) {
                    if (is_string($att) && str_starts_with($att, 'sticker')) {
                        $hasExistingSticker = true;
                        break;
                    } elseif (is_array($att) && ($att['type'] ?? '') === 'sticker') {
                        $hasExistingSticker = true;
                        break;
                    } elseif (is_object($att) && ($att->type ?? '') === 'sticker') {
                        $hasExistingSticker = true;
                        break;
                    }
                }
            }
            if ($hasExistingSticker) {
                $this->fail(920, "Can't edit message with sticker");
            }
        }

        $attachment_checked = parseAttachments($attachment, ["photo", "video", "doc", "audio", "wall", "sticker"]);
        $attachment_secure = [];

        foreach ($attachment_checked as $item) {
            if ($item instanceof \openvk\Web\Models\Entities\Messages\Sticker) {
                $this->fail(920, "Can't edit message with sticker");
            }
            if (!$item || !$item->canBeViewedBy($this->getUser())) {
                continue;
            } else {
                $attachment_secure[] = $item->getAttachmentString();
            }
        }

        $cleanMessage = trim(preg_replace('/[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+/u', ' ', $message));
        if ($cleanMessage === '') {
            $message = '';
        } else {
            $message = preg_replace('/^[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+|[\s\x{200b}\x{feff}\x{00a0}\x{200c}\x{200d}]+$/u', '', $message);
        }

        if (empty($message) && sizeof($attachment_secure) == 0) {
            $this->fail(100, "Empty messages are not allowed");
        }

        $params = [
            "peer_id"               => (string) $resolvedId,
            "message_id"            => (string) $message_id,
            "message"               => $message,
            "attachment"            => implode(",", $attachment_secure),
            "keep_forward_messages" => (string) $keep_forward_messages,
        ];

        $result = $this->invoke("messages.edit", $params, $group_id);

        return (int) $result;
    }

    public function delete(
        string $message_ids = "",
        int $delete_for_all = 0,
        int $spam = 0,
        int $peer_id = 0,
        int $chat_id = -1,
        int $group_id = 0,
        string $domain = "",
        int $user_id = -1
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_ids is empty");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);

        $params = [
            "message_ids"    => $message_ids,
            "delete_for_all" => (string) $delete_for_all,
            "spam"           => (string) $spam,
        ];

        if ($resolvedId !== 0 && !is_null($resolvedId)) {
            $params["peer_id"] = (string) $resolvedId;
        }

        $res = $this->invoke("messages.delete", $params, $group_id);
        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return 1;
        }

        return $res;
    }

    public function restore(
        int $message_id = 0,
        int $peer_id = 0,
        int $group_id = 0,
        string $domain = "",
        int $user_id = -1
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($message_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_id is required");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);
        if (is_null($resolvedId) || $resolvedId === 0) {
            $this->fail(936, "There is no peer with this id");
        }

        $params = [
            "peer_id"    => (string) $resolvedId,
            "message_id" => (string) $message_id,
        ];

        return (int) $this->invoke("messages.restore", $params, $group_id);
    }

    public function search(
        string $q = "",
        int $peer_id = 0,
        string $domain = "",
        int $user_id = -1,
        int $chat_id = -1,
        string $date = "",
        int $preview_length = 0,
        int $offset = 0,
        int $count = 20,
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ) {
        $this->requireUser();

        if (empty($q)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: q is empty");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);

        $params = [
            "q"              => $q,
            "count"          => (string) min(abs($count), 100),
            "offset"         => (string) abs($offset),
            "preview_length" => (string) max(0, $preview_length),
            "extended"       => $extended ? "1" : "0",
            "fields"         => $fields,
        ];

        if ($resolvedId !== 0 && !is_null($resolvedId)) {
            $params["peer_id"] = (string) $resolvedId;
        }

        if (!empty($date)) {
            $params["date"] = $date; // DDMMYYYY
        }

        $data = $this->invoke("messages.search", $params, $group_id);

        if (!empty($data['items'])) {
            $isLegacy = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && defined("VKAPI_DECL_VER_MINOR") && VKAPI_DECL_VER_MINOR < 80);
            $chatsRepo = new ChatRepo();
            $loadedChats = [];

            foreach ($data['items'] as &$item) {
                $this->sanitizeMessageAttachmentsRecursive($item);

                $cId = !empty($item['chat_id']) ? (int) $item['chat_id'] : (!empty($item['peer_id']) && (int) $item['peer_id'] > 2000000000 ? ((int) $item['peer_id'] - 2000000000) : 0);
                if ($cId > 0) {
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? $this->getDefaultChatTitle($cId);
                        }
                        if (empty($item['photo_50'])) {
                            $item['photo_50'] = $chatStruct['photo_50'] ?? "";
                            $item['photo_100'] = $chatStruct['photo_100'] ?? "";
                            $item['photo_200'] = $chatStruct['photo_200'] ?? "";
                        }
                    }
                }

                $item['mid']  = (int) ($item['id'] ?? 0);
                $item['uid']  = (int) ($item['user_id'] ?? $item['from_id'] ?? 0);
                $item['body'] = (string) ($item['body'] ?? $item['text'] ?? "");
                if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 4) {
                    if (isset($item['chat_active'])) {
                        $item['chat_active'] = is_array($item['chat_active'])
                            ? implode(',', array_filter($item['chat_active']))
                            : (string) $item['chat_active'];
                    }
                }
            }
            unset($item);
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($data, $fields);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $items = $data['items'] ?? [];
            $total = (int) ($data['count'] ?? count($items));
            if ($total === 0 || empty($items)) {
                return [0];
            }
            return array_merge([$total], $items);
        }

        return $data;
    }

    public function pin(
        int $peer_id = 0,
        int $message_id = 0,
        int $cmid = 0,
        int $conversation_message_id = 0,
        string $domain = "",
        int $user_id = -1,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($message_id <= 0) {
            $message_id = $conversation_message_id > 0 ? $conversation_message_id : $cmid;
        }

        if ($message_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_id is required");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);
        if (!$resolvedId) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id is required");
        }

        $params = [
            "peer_id"    => (string) $resolvedId,
            "message_id" => (string) $message_id,
        ];

        if ($conversation_message_id > 0 || $cmid > 0) {
            $params["conversation_message_id"] = (string) ($conversation_message_id > 0 ? $conversation_message_id : $cmid);
        }

        $data = $this->invoke("messages.pin", $params, $group_id);
        if (is_array($data) && !empty($data['attachments'])) {
            $this->replaceAttachments($data['attachments'], ["gift"]);
        }

        return $data;
    }

    public function unpin(
        int $peer_id = 0,
        string $domain = "",
        int $user_id = -1,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);
        if (!$resolvedId) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id is required");
        }

        $params = [
            "peer_id" => (string) $resolvedId,
        ];

        return (int) $this->invoke("messages.unpin", $params, $group_id);
    }

    public function getImportantMessages(
        int $count = 20,
        int $offset = 0,
        int $start_message_id = 0,
        int $preview_length = 0,
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ) {
        $this->requireUser();

        $params = [
            "count"            => (string) $count,
            "offset"           => (string) $offset,
            "start_message_id" => (string) $start_message_id,
            "preview_length"   => (string) $preview_length,
            "extended"         => (string) $extended,
            "fields"           => $fields,
        ];

        $data = $this->invoke("messages.getImportantMessages", $params, $group_id);

        $isLegacy = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && defined("VKAPI_DECL_VER_MINOR") && VKAPI_DECL_VER_MINOR < 80);

        if ($isLegacy) {
            $msgItems = &$data['items'];
        } else {
            $msgItems = &$data['messages']['items'];
        }

        if (!empty($msgItems) && is_array($msgItems)) {
            $chatsRepo = new ChatRepo();
            $loadedChats = [];

            foreach ($msgItems as &$item) {
                $this->sanitizeMessageAttachmentsRecursive($item);

                if ($isLegacy && !empty($item['chat_id'])) {
                    $cId = (int) $item['chat_id'];
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? $this->getDefaultChatTitle($cId);
                        }
                        if (empty($item['photo_50'])) {
                            $item['photo_50'] = $chatStruct['photo_50'] ?? "";
                            $item['photo_100'] = $chatStruct['photo_100'] ?? "";
                            $item['photo_200'] = $chatStruct['photo_200'] ?? "";
                        }
                    }
                }
            }
            unset($item);
        }

        if ($extended == 1 || !empty($fields)) {
            $this->hydrateExtendedData($data, $fields);
        }

        return $data;
    }

    public function markAsImportant(
        string $message_ids = "",
        string $cmids = "",
        string $conversation_message_ids = "",
        int $peer_id = 0,
        int $important = 1,
        int $group_id = 0,
        int $user_id = -1,
        int $chat_id = -1,
        string $domain = ""
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);
        $cmidsParam = !empty($conversation_message_ids) ? $conversation_message_ids : $cmids;

        if (empty($message_ids) && empty($cmidsParam)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_ids is empty");
        }

        $params = [
            "important" => (string) $important,
        ];

        if (!empty($message_ids)) {
            $params["message_ids"] = $message_ids;
        }
        if (!empty($cmidsParam)) {
            $params["conversation_message_ids"] = $cmidsParam;
            $params["cmids"] = $cmidsParam;
        }
        if ($resolvedId) {
            $params["peer_id"] = (string) $resolvedId;
        }

        return $this->invoke("messages.markAsImportant", $params, $group_id);
    }

    public function markAsRead(
        int $peer_id = 0,
        int $start_message_id = 0,
        string $message_ids = "",
        int $user_id = -1,
        int $chat_id = -1,
        string $domain = "",
        int $mark_conversation_as_read = 0,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($peer_id === 0 && empty($message_ids) && $start_message_id === 0 && $user_id === -1 && $chat_id === -1 && empty($domain)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id, start_message_id or message_ids is required");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);

        $params = [
            "start_message_id"          => (string) $start_message_id,
            "message_ids"               => $message_ids,
            "mark_conversation_as_read" => (string) $mark_conversation_as_read,
        ];

        if ($resolvedId !== 0 && !is_null($resolvedId)) {
            $params["peer_id"] = (string) $resolvedId;
        }

        $this->invoke("messages.markAsRead", $params, $group_id);

        return 1;
    }

    public function getByConversationMessageId(
        int $peer_id = 0,
        string $conversation_message_ids = "",
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ) {
        $this->requireUser();

        if ($peer_id === 0 || empty($conversation_message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id or conversation_message_ids is empty");
        }

        $params = [
            "peer_id"                  => (string) $peer_id,
            "conversation_message_ids" => $conversation_message_ids,
            "extended"                 => (string) $extended,
            "fields"                   => $fields,
        ];

        $data = $this->invoke("messages.getByConversationMessageId", $params, $group_id);

        if (!empty($data['items'])) {
            foreach ($data['items'] as &$item) {
                $this->sanitizeMessageAttachmentsRecursive($item);
            }
            unset($item);
        }

        if ($extended) {
            $this->hydrateExtendedData($data, $fields);
        }

        return $data;
    }

    // ----------------------------------
    //               Chats
    // ----------------------------------

    public function createChat(string $title = "", string $user_ids = "", int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        //$this->fail(-5, "Method is disabled");

        /*if (empty($title)) {
            $this->fail(100, "One of the parameters is missing: title");
        }*/

        /*if (empty($user_ids)) {
            $this->fail(100, "One of the parameters is missing: user_ids");
        }*/

        $rawIds = preg_split("%, ?%", $user_ids);
        $targetUserIds = array_filter(array_map('intval', $rawIds));
        $users = (new USRRepo)->getByIds($targetUserIds);
        $currentUser = $this->getUser();

        foreach ($users as $usr) {
            $usrid = $usr->getId();
            if ($usrid === $currentUser->getId()) {
                continue;
            }

            if (!$currentUser->isFriendsWith($usr)) {
                $this->fail(15, "Access denied: user with ID " . $usrid . " is not your friend");
            }
        }

        $params = [
            "title"    => $title,
            "user_ids" => $user_ids,
        ];

        $chatId = $this->invoke("messages.createChat", $params, $group_id);
        $chatId = (int) $chatId;

        $chRepo = new ChatRepo();
        $chRepo->create($chatId, $title, "", null);

        return (int) $chatId;
    }

    public function getChat(
        int $chat_id = 0,
        string $chat_ids = "",
        string $fields = "",
        string $name_case = "nom",
        int $group_id = 0
    ) {
        $this->requireUser();

        if ($chat_id <= 0 && empty($chat_ids)) {
            $chat_id = (int) ($_GET['chat_id'] ?? $_POST['chat_id'] ?? 0);
            $chat_ids = (string) ($_GET['chat_ids'] ?? $_POST['chat_ids'] ?? '');
        }

        $rawIds = [];
        if ($chat_id > 0) {
            $rawIds[] = $chat_id > 2000000000 ? $chat_id - 2000000000 : $chat_id;
        }

        if (!empty($chat_ids)) {
            $split = preg_split("%, ?%", $chat_ids);
            foreach ($split as $id) {
                $val = (int) $id;
                if ($val > 0) {
                    $rawIds[] = $val > 2000000000 ? $val - 2000000000 : $val;
                }
            }
        }

        $rawIds = array_values(array_unique(array_filter($rawIds)));
        if (empty($rawIds)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: chat_id or chat_ids is required");
        }

        $peerIds = array_map(fn($id) => (string) (2000000000 + $id), $rawIds);

        $imResponse = $this->invoke("messages.getConversationsById", [
            "peer_ids" => implode(',', $peerIds),
            "extended" => "1",
        ], $group_id);

        $chatsRepo = new ChatRepo();
        $resultChats = [];

        $itemsMap = [];
        if (!empty($imResponse['items'])) {
            foreach ($imResponse['items'] as $item) {
                $peer = $item['conversation']['peer'] ?? null;
                if ($peer && $peer['type'] === 'chat') {
                    $localId = (int) ($peer['id'] - 2000000000);
                    $itemsMap[$localId] = $item['conversation'];
                }
            }
        }

        $userProfilesMap = [];
        if (!empty($fields) && !empty($imResponse['profiles'])) {
            $uIDs = [];
            foreach ($imResponse['profiles'] as $p) {
                $idVal = is_array($p) ? ($p['id'] ?? 0) : (int) $p;
                if ($idVal > 0) {
                    $uIDs[] = $idVal;
                }
            }
            if (!empty($uIDs)) {
                $apiUsers = (new APIUsers())->get(implode(',', array_unique($uIDs)), $fields);
                foreach ($apiUsers as $uStruct) {
                    $uID = is_array($uStruct) ? ($uStruct['id'] ?? 0) : (is_object($uStruct) ? ($uStruct->id ?? 0) : 0);
                    if ($uID > 0) {
                        $userProfilesMap[$uID] = $uStruct;
                    }
                }
            }
        }

        foreach ($rawIds as $localChatId) {
            $chatEntity = $chatsRepo->getByChatId($localChatId);

            if (!$chatEntity) {
                $chatEntity = $chatsRepo->create($localChatId, $this->getDefaultChatTitle($localChatId));
            }

            if (isset($itemsMap[$localChatId])) {
                $convData = $itemsMap[$localChatId];
                $settings = $convData['chat_settings'] ?? [];
                if (isset($convData['peer'])) {
                    $settings['peer'] = $convData['peer'];
                }
                if (isset($convData['can_write'])) {
                    $settings['can_write'] = $convData['can_write'];
                }
                if (!empty($settings['state']) && ($settings['state'] === 'left' || $settings['state'] === 'kicked')) {
                    $settings['left'] = 1;
                    if ($settings['state'] === 'kicked') {
                        $settings['kicked'] = 1;
                    }
                }
                $chatEntity->setData($settings);
            }

            $chatStruct = $chatEntity->toVkApiStruct($this->getUser());
            $chatStruct['id'] = $localChatId;

            if (isset($itemsMap[$localChatId]['chat_settings'])) {
                $settings = $itemsMap[$localChatId]['chat_settings'];
                if (isset($settings['admin_id'])) {
                    $chatStruct['admin_id'] = (int) $settings['admin_id'];
                }
                if (!empty($settings['state']) && $settings['state'] === 'kicked') {
                    $chatStruct['kicked'] = 1;
                }
                if (!empty($settings['state']) && $settings['state'] === 'left') {
                    $chatStruct['left'] = 1;
                }
            }

            if (!empty($chatStruct['users']) && (!empty($fields) || (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5))) {
                $missingUserIds = [];
                foreach ($chatStruct['users'] as $uId) {
                    $uIdInt = (int) $uId;
                    if ($uIdInt > 0 && !isset($userProfilesMap[$uIdInt])) {
                        $missingUserIds[] = $uIdInt;
                    }
                }
                if (!empty($missingUserIds)) {
                    $reqFields = !empty($fields) ? $fields : "online,first_name,last_name,photo_medium_rec,photo_rec";
                    $moreUsers = (new APIUsers())->get(implode(',', array_unique($missingUserIds)), $reqFields);
                    foreach ($moreUsers as $uStruct) {
                        $uID = is_array($uStruct) ? ($uStruct['id'] ?? 0) : (is_object($uStruct) ? ($uStruct->id ?? 0) : 0);
                        if ($uID > 0) {
                            $userProfilesMap[$uID] = $uStruct;
                        }
                    }
                }

                $chatAdminId = (int) ($chatStruct['admin_id'] ?? 0);
                $memberInvitedByMap = [];
                try {
                    $chatRows = \Chandler\Database\DatabaseConnection::i()->getContext()
                        ->table('openvk_im.conversation_members')
                        ->where('internal_chat_id', (string) $localChatId)
                        ->fetchAll();
                    foreach ($chatRows as $row) {
                        $memberInvitedByMap[(int) $row->user_id] = (int) ($row->invited_by ?: $chatAdminId);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                $hydratedUsers = [];
                foreach ($chatStruct['users'] as $uId) {
                    $uIdInt = (int) $uId;
                    $invitedBy = $memberInvitedByMap[$uIdInt] ?? $chatAdminId;
                    if ($invitedBy <= 0) {
                        $invitedBy = $uIdInt;
                    }

                    if (isset($userProfilesMap[$uIdInt])) {
                        $uObj = is_array($userProfilesMap[$uIdInt]) ? (object) $userProfilesMap[$uIdInt] : clone $userProfilesMap[$uIdInt];
                    } else {
                        $uObj = (object) [
                            'id'         => $uIdInt,
                            'uid'        => $uIdInt,
                            'first_name' => 'DELETED',
                            'last_name'  => '',
                        ];
                    }

                    $uObj->uid = $uIdInt;
                    $uObj->id = $uIdInt;
                    $uObj->invited_by = $invitedBy;
                    if (!isset($uObj->photo_rec)) {
                        $uObj->photo_rec = (string) ($uObj->photo_50 ?? $uObj->photo ?? '');
                    }
                    if (!isset($uObj->photo_medium_rec)) {
                        $uObj->photo_medium_rec = (string) ($uObj->photo_100 ?? $uObj->photo_200 ?? $uObj->photo_rec);
                    }
                    if (!isset($uObj->online)) {
                        $uObj->online = 0;
                    }

                    $hydratedUsers[] = $uObj;
                }
                $chatStruct['users'] = $hydratedUsers;
            }

            $resultChats[] = $chatStruct;
        }

        if ($chat_id > 0 && empty($chat_ids)) {
            return (object) ($resultChats[0] ?? []);
        }

        return $resultChats;
    }

    public function addChatUser(int $peer_id = 0, string $user_id = "", int $group_id = 0, int $chat_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if (empty($user_id)) {
            $user_id = (string) ($_POST['user_id'] ?? $_GET['user_id'] ?? '');
        }

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id === 0 || empty($user_id)) {
            $this->fail(100, "One of the parameters is missing: peer_id or user_id");
        }

        if ($peer_id < 2000000000) {
            $this->fail(15, "Access denied: cannot add user to direct message");
        }

        $rawIds = preg_split("%, ?%", (string) $user_id);
        $targetUserIds = array_filter(array_map('intval', $rawIds));
        $users = (new USRRepo)->getByIds($targetUserIds);
        $currentUser = $this->getUser();

        foreach ($users as $usr) {
            if (!$usr || $usr->getRealId() === $currentUser->getId()) {
                continue;
            }

            if (!$currentUser->isFriendsWith($usr)) {
                $this->fail(15, "Access denied: user with ID " . $usr->getRealId() . " is not your friend");
            }

            if (!$usr->getPrivacyPermission("messages.add_to_chats", $currentUser)) {
                $this->fail(15, "Access denied: user with ID " . $usr->getRealId() . " disabled adding to chats");
            }
        }

        foreach ($targetUserIds as $targetId) {
            $params = [
                "peer_id" => (string) $peer_id,
                "user_id" => (string) $targetId,
            ];

            $this->invoke("messages.addChatUser", $params, $group_id);
        }

        return 1;
    }

    public function removeChatUser(int $peer_id = 0, int $user_id = 0, int $group_id = 0, int $chat_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id === 0) {
            $this->fail(100, "One of the parameters is missing: peer_id");
        }

        if ($peer_id < 2000000000) {
            $this->fail(15, "Access denied: cannot remove user from direct message");
        }

        $currentUser = $this->getUser();

        if ($user_id === 0) {
            $user_id = $currentUser->getId();
        }

        $params = [
            "peer_id" => $peer_id,
            "user_id" => $user_id,
        ];

        $this->invoke("messages.removeChatUser", $params, $group_id);

        return 1;
    }

    public function getConversations(
        int $offset = 0,
        int $count = 20,
        string $filter = "all",
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ): array {
        $this->requireUser();
        $currentUserId = $this->getUser()->getId();

        $params = [
            "offset"   => (string) $offset,
            "count"    => (string) $count,
            "filter"   => $filter,
            "extended" => (string) $extended,
        ];

        $payload = $this->invoke("messages.getConversations", $params, $group_id);

        if (empty($payload['items'])) {
            return $payload;
        }

        $chatIds = [];
        foreach ($payload['items'] as $item) {
            $peer = $item['conversation']['peer'] ?? null;
            if ($peer && $peer['type'] === 'chat') {
                $chatIds[] = (int) ($peer['id'] - 2000000000);
            }
        }

        if ($extended && !empty($payload['chats'])) {
            foreach ($payload['chats'] as $chat) {
                $chatId = is_array($chat) ? ($chat['id'] ?? 0) : (int) $chat;
                if ($chatId > 2000000000) {
                    $chatIds[] = (int) ($chatId - 2000000000);
                }
            }
        }

        $chatIds = array_unique(array_filter($chatIds));
        $loadedChats = [];

        if (!empty($chatIds)) {
            $chatsRepo = new ChatRepo();
            foreach ($chatIds as $cId) {
                $chatObj = $chatsRepo->getByChatId($cId);
                $loadedChats[$cId] = $chatObj ?: null;
            }
        }

        foreach ($payload['items'] as &$item) {
            $conversation = &$item['conversation'];
            $peer = $conversation['peer'] ?? null;

            if ($peer && $peer['type'] === 'chat') {
                $chatId = (int) ($peer['id'] - 2000000000);
                $chatEntity = $loadedChats[$chatId] ?? null;

                if (!$chatEntity) {
                    $chatsRepo = new ChatRepo();
                    $chatEntity = $chatsRepo->create($chatId, $this->getDefaultChatTitle($chatId));
                    $loadedChats[$chatId] = $chatEntity;
                }

                if ($chatEntity) {
                    if (!empty($conversation["chat_settings"])) {
                        $chatEntity->setData($conversation["chat_settings"]);
                    }
                    $conversation['chat_settings'] = $chatEntity->toChatSettingsStruct($this->getUser());
                }
            }

            if (!empty($conversation['chat_settings']['pinned_message']) && is_array($conversation['chat_settings']['pinned_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($conversation['chat_settings']['pinned_message']);
            }
            if (!empty($conversation['pinned_message']) && is_array($conversation['pinned_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($conversation['pinned_message']);
            }

            if (!empty($item['last_message']) && is_array($item['last_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($item['last_message']);
            }

            $this->enrichConversationCanWrite($conversation, $currentUserId);
        }
        unset($item);

        if ($extended) {
            $this->hydrateExtendedData($payload, $fields, $loadedChats);
        }

        return $payload;
    }

    public function getDialogs(
        int $offset = 0,
        int $count = 20,
        int $preview_length = 0,
        int $unread = 0,
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0,
        int $user_id = 0,
        int $peer_id = 0,
        int $chat_id = 0
    ): array {
        $this->requireUser();

        $resolvedSpecificPeer = $this->resolvePeer($user_id, $peer_id, $chat_id);
        if ($resolvedSpecificPeer && $resolvedSpecificPeer > 0) {
            $payload = $this->invoke("messages.getConversationsById", [
                "peer_ids" => (string) $resolvedSpecificPeer,
                "extended" => (string) $extended,
            ], $group_id);
        } else {
            $filter = $unread ? "unread" : "all";
            $fetchCount = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) ? 200 : min(abs($count), 200);
            $params = [
                "offset"   => (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) ? "0" : (string) $offset,
                "count"    => (string) $fetchCount,
                "filter"   => $filter,
                "extended" => (string) $extended,
            ];

            $payload = $this->invoke("messages.getConversations", $params, $group_id);
        }

        if (empty($payload['items'])) {
            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                return [0];
            }
            return [
                "count" => 0,
                "items" => [],
            ];
        }

        $chatIds = [];
        foreach ($payload['items'] as $item) {
            $peer = $item['conversation']['peer'] ?? null;
            if ($peer && $peer['type'] === 'chat') {
                $chatIds[] = (int) ($peer['id'] - 2000000000);
            }
        }

        $chatIds = array_unique(array_filter($chatIds));
        $loadedChats = [];
        if (!empty($chatIds)) {
            $chatsRepo = new ChatRepo();
            foreach ($chatIds as $cId) {
                $chatObj = $chatsRepo->getByChatId($cId);
                $loadedChats[$cId] = $chatObj ?: null;
            }
        }

        $flatMessages = [];
        $currentUserId = $this->getUser()->getId();

        foreach ($payload['items'] as $item) {
            $conv = $item['conversation'] ?? [];
            $peer = $conv['peer'] ?? [];
            $peerId = (int) ($peer['id'] ?? 0);
            $peerType = $peer['type'] ?? 'user';
            $lastMsg = $item['last_message'] ?? [];

            if (empty($lastMsg)) {
                continue;
            }

            $msgOut = (int) ($lastMsg['out'] ?? 0);
            $text = (string) ($lastMsg['body'] ?? $lastMsg['text'] ?? "");
            $hasEmoji = (int) ($lastMsg['emoji'] ?? 0);
            if (!$hasEmoji && !empty($text)) {
                $hasEmoji = (preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $text)) ? 1 : 0;
            }

            $msgObj = [
                "id"          => (int) ($lastMsg['id'] ?? 0),
                "date"        => (int) ($lastMsg['date'] ?? 0),
                "out"         => $msgOut,
                "read_state"  => (int) ($lastMsg['read_state'] ?? 0),
                "title"       => (string) ($lastMsg['title'] ?? ""),
                "body"        => $text,
                "attachments" => $lastMsg['attachments'] ?? [],
                "fwd_messages"=> $lastMsg['fwd_messages'] ?? [],
                "emoji"       => $hasEmoji,
                "deleted"     => 0,
            ];

            if (!empty($lastMsg['important'])) {
                $msgObj['important'] = true;
            }

            if (!empty($lastMsg['action'])) {
                $msgObj['action'] = $lastMsg['action'];
                if (!empty($lastMsg['action_mid'])) {
                    $msgObj['action_mid'] = (int) $lastMsg['action_mid'];
                }
                if (!empty($lastMsg['action_text'])) {
                    $msgObj['action_text'] = (string) $lastMsg['action_text'];
                }
                if (!empty($lastMsg['action_email'])) {
                    $msgObj['action_email'] = (string) $lastMsg['action_email'];
                }
            }

            if ($preview_length > 0) {
                $msgObj['body'] = ovk_truncate_words($msgObj['body'], $preview_length);
            }

            $this->sanitizeMessageAttachmentsRecursive($msgObj);

            if ($peerType === 'chat') {
                $localChatId = $peerId > 2000000000 ? ($peerId - 2000000000) : $peerId;
                $chatEntity = $loadedChats[$localChatId] ?? null;

                $msgObj['user_id'] = (int) ($lastMsg['from_id'] ?? $currentUserId);
                $msgObj['chat_id'] = $localChatId;

                $chatSettings = $conv['chat_settings'] ?? [];
                $members = $chatSettings['members'] ?? $chatSettings['users'] ?? [];

                if (!$chatEntity) {
                    try {
                        $chatsRepo = new ChatRepo();
                        $chatEntity = $chatsRepo->create($localChatId, $this->getDefaultChatTitle($localChatId));
                        $loadedChats[$localChatId] = $chatEntity;
                    } catch (\Exception $e) {
                        $chatEntity = null;
                    }
                }

                if ($chatEntity) {
                    if (!empty($chatSettings)) {
                        $chatEntity->setData($chatSettings);
                    }
                    $chatStruct = $chatEntity->toChatSettingsStruct($this->getUser());
                    $msgObj['title'] = !empty($msgObj['title']) ? $msgObj['title'] : ($chatStruct['title'] ?? $this->getDefaultChatTitle($localChatId));
                    $msgObj['admin_id'] = (int) ($chatStruct['admin_id'] ?? ($chatSettings['admin_id'] ?? 0));
                    $msgObj['users_count'] = (int) ($chatStruct['members_count'] ?? count($members));
                    $msgObj['chat_active'] = $chatStruct['active_ids'] ?? array_slice($members, 0, 10);
                    $msgObj['photo_50'] = $chatStruct['photo_50'] ?? "";
                    $msgObj['photo_100'] = $chatStruct['photo_100'] ?? "";
                    $msgObj['photo_200'] = $chatStruct['photo_200'] ?? "";

                    $chatEntity->setData([
                        "title"      => $msgObj['title'],
                        "admin_id"   => $msgObj['admin_id'],
                        "members"    => $msgObj['chat_active'],
                        "users"      => $msgObj['chat_active'],
                        "photo_50"   => $msgObj['photo_50'],
                        "photo_100"  => $msgObj['photo_100'],
                        "photo_200"  => $msgObj['photo_200'],
                    ]);
                } else {
                    $msgObj['title'] = !empty($msgObj['title']) ? $msgObj['title'] : $this->getDefaultChatTitle($localChatId);
                    $msgObj['admin_id'] = (int) ($chatSettings['admin_id'] ?? 0);
                    $msgObj['users_count'] = count($members);
                    $msgObj['chat_active'] = array_slice($members, 0, 10);
                }
            } else {
                $msgObj['user_id'] = $peerId;
            }

            $msgObj['uid'] = $msgObj['user_id'];
            $msgObj['mid'] = $msgObj['id'];

            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
                if (empty($msgObj['attachments'])) {
                    unset($msgObj['attachments']);
                }
                if (empty($msgObj['fwd_messages'])) {
                    unset($msgObj['fwd_messages']);
                }
            }

            if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 4) {
                if (isset($msgObj['chat_active'])) {
                    $msgObj['chat_active'] = is_array($msgObj['chat_active'])
                        ? implode(',', array_filter($msgObj['chat_active']))
                        : (string) $msgObj['chat_active'];
                }
            }

            $flatMessages[$peerId] = $msgObj;
        }

        $flatMessages = array_values($flatMessages);

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $total = (int) ($payload['count'] ?? count($flatMessages));
            if ($total === 0 || empty($flatMessages)) {
                return [0];
            }
            if ($resolvedSpecificPeer && $resolvedSpecificPeer > 0) {
                return array_merge([$total], $flatMessages);
            }
            $paged = array_slice($flatMessages, $offset, min(abs($count), 200));
            return array_merge([$total], $paged);
        }

        $result = [
            "count" => (int) ($payload['count'] ?? count($flatMessages)),
            "items" => $flatMessages,
        ];

        if (isset($payload['unread_count'])) {
            $result["unread_dialogs"] = (int) $payload['unread_count'];
        }

        if ($extended == 1) {
            $userIDs = [];
            $groupIDs = [];
            $chatIDs = [];

            if (!empty($flatMessages)) {
                foreach ($flatMessages as $item) {
                    if (!empty($item['user_id'])) {
                        if ($item['user_id'] > 0 && $item['user_id'] < 2000000000) {
                            $userIDs[] = (int) $item['user_id'];
                        } elseif ($item['user_id'] < 0) {
                            $groupIDs[] = abs((int) $item['user_id']);
                        }
                    }
                    if (!empty($item['admin_id']) && $item['admin_id'] > 0) {
                        $userIDs[] = (int) $item['admin_id'];
                    }
                    if (!empty($item['action_mid']) && $item['action_mid'] > 0) {
                        $userIDs[] = (int) $item['action_mid'];
                    }
                    if (!empty($item['chat_active'])) {
                        foreach ($item['chat_active'] as $uid) {
                            if ($uid > 0) {
                                $userIDs[] = (int) $uid;
                            }
                        }
                    }
                    if (!empty($item['chat_id'])) {
                        $chatIDs[] = (int) $item['chat_id'];
                    }
                    if (!empty($item['fwd_messages'])) {
                        $this->collectEntityIdsRecursive($item, $userIDs, $groupIDs);
                    }
                }
            }

            if (!empty($payload['profiles'])) {
                foreach ($payload['profiles'] as $p) {
                    $userIDs[] = is_array($p) ? ($p['id'] ?? 0) : (int) $p;
                }
            }
            if (!empty($payload['groups'])) {
                foreach ($payload['groups'] as $g) {
                    $groupIDs[] = abs(is_array($g) ? ($g['id'] ?? 0) : (int) $g);
                }
            }
            if (!empty($payload['chats'])) {
                foreach ($payload['chats'] as $c) {
                    $chatIDs[] = is_array($c) ? ($c['id'] ?? 0) : (int) $c;
                }
            }

            $extPayload = [
                'profiles' => array_values(array_unique(array_filter($userIDs))),
                'groups'   => array_values(array_unique(array_filter($groupIDs))),
                'chats'    => array_values(array_unique(array_filter($chatIDs))),
            ];

            $this->hydrateExtendedData($extPayload, $fields, $loadedChats);

            if (!empty($extPayload['profiles'])) {
                $result['profiles'] = $extPayload['profiles'];
            }
            if (!empty($extPayload['groups'])) {
                $result['groups'] = $extPayload['groups'];
            }
            if (!empty($extPayload['chats'])) {
                $result['chats'] = $extPayload['chats'];
            }
        }

        return $result;
    }

    // Legacy method: messages.searchDialogs (deprecated since 5.80, replaced by messages.searchConversations)
    public function searchDialogs(
        string $q = '',
        int $limit = 20,
        string $fields = 'photo_50,photo_100,photo_200,online',
        int $group_id = 0
    ): array {
        $this->requireUser();
        $this->ensureBrokerActive();

        $q = trim($q);
        if (empty($q)) {
            return [];
        }

        $limit = ($limit > 0 && $limit <= 100) ? $limit : 20;

        // 1. Search conversations from IM backend
        $convs = $this->searchConversations($q, 1, $group_id);
        $items = $convs['items'] ?? [];

        $results = [];
        $addedUserIds = [];
        $addedChatIds = [];

        $chatsRepo = new ChatRepo();

        foreach ($items as $item) {
            if (count($results) >= $limit) {
                break;
            }

            $peer = $item['conversation']['peer'] ?? null;
            if (!$peer) continue;

            $peerType = $peer['type'] ?? 'user';
            $peerId = (int) ($peer['id'] ?? 0);

            if ($peerType === 'user' && $peerId > 0 && !isset($addedUserIds[$peerId])) {
                $addedUserIds[$peerId] = true;
                $apiUsers = (new APIUsers())->get((string) $peerId, $fields);
                if (!empty($apiUsers[0])) {
                    $uObj = is_array($apiUsers[0]) ? (object) $apiUsers[0] : $apiUsers[0];
                    $uObj->type = "profile";
                    $results[] = $uObj;
                }
            } elseif ($peerType === 'chat') {
                $localChatId = $peerId > 2000000000 ? ($peerId - 2000000000) : $peerId;
                if (!isset($addedChatIds[$localChatId])) {
                    $addedChatIds[$localChatId] = true;
                    $chatEntity = $chatsRepo->getByChatId($localChatId);
                    if (!$chatEntity) {
                        $chatEntity = $chatsRepo->create($localChatId, $this->getDefaultChatTitle($localChatId));
                    }
                    $chatStruct = $chatEntity->toVkApiStruct($this->getUser());
                    $chatStruct['type'] = 'chat';
                    $chatStruct['id'] = $localChatId;
                    $results[] = (object) $chatStruct;
                }
            }
        }

        // 2. Fallback search users matching query if limit not yet reached
        if (count($results) < $limit) {
            $usersRepo = new USRRepo();
            $stream = $usersRepo->find($q);
            $moreUserIds = [];
            foreach ($stream as $user) {
                $uId = (int) $user->getId();
                if (!isset($addedUserIds[$uId])) {
                    $addedUserIds[$uId] = true;
                    $moreUserIds[] = $uId;
                    if (count($results) + count($moreUserIds) >= $limit) {
                        break;
                    }
                }
            }
            if (!empty($moreUserIds)) {
                $apiUsers = (new APIUsers())->get(implode(',', $moreUserIds), $fields);
                foreach ($apiUsers as $u) {
                    $uObj = is_array($u) ? (object) $u : $u;
                    $uObj->type = "profile";
                    $results[] = $uObj;
                    if (count($results) >= $limit) {
                        break;
                    }
                }
            }
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $formatted = [];
            foreach ($results as $item) {
                $type = is_object($item) ? ($item->type ?? 'profile') : ($item['type'] ?? 'profile');
                if ($type === 'profile') {
                    $u = is_object($item) ? clone $item : (object) $item;
                    $uid = (int) ($u->id ?? $u->uid ?? 0);
                    $first = (string) ($u->first_name ?? '');
                    $last = (string) ($u->last_name ?? '');
                    $photoRec = (string) ($u->photo_rec ?? $u->photo_50 ?? $u->photo ?? '');
                    $photoMed = (string) ($u->photo_medium_rec ?? $u->photo_100 ?? $u->photo_200 ?? $photoRec);
                    $online = (int) ($u->online ?? 0);

                    $formatted[] = (object) [
                        'type'             => 'profile',
                        'uid'              => $uid,
                        'first_name'       => $first,
                        'last_name'        => $last,
                        'photo_rec'        => $photoRec,
                        'photo_medium_rec' => $photoMed,
                        'online'           => $online,
                        'profile'          => $u,
                    ];
                } elseif ($type === 'chat') {
                    $c = is_object($item) ? (array) $item : (array) $item;
                    $chatId = $c['chat_id'] ?? ($c['id'] ?? 0);
                    $title = $c['title'] ?? '';
                    $chatObj = [
                        'type'    => 'chat',
                        'chat_id' => (int) $chatId,
                        'title'   => (string) $title,
                    ];
                    if (isset($c['users'])) {
                        $chatObj['users'] = $c['users'];
                    }
                    if (isset($c['admin_id'])) {
                        $chatObj['admin_id'] = $c['admin_id'];
                    }
                    $formatted[] = (object) $chatObj;
                } else {
                    $formatted[] = $item;
                }
            }
            return $formatted;
        }

        return $results;
    }

    // Legacy alias: messages.getChatUsers (deprecated since 5.80, replaced by messages.getConversationMembers)
    public function getChatUsers(int $chat_id = 0, string $fields = "", string $name_case = "nom", int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        if ($chat_id <= 0) {
            $chat_id = (int) ($_GET['chat_id'] ?? $_POST['chat_id'] ?? 0);
        }

        if ($chat_id <= 0) {
            if (!empty($_GET['peer_id']) && (int)$_GET['peer_id'] > 2000000000) {
                $chat_id = (int)$_GET['peer_id'] - 2000000000;
            } elseif (!empty($_POST['peer_id']) && (int)$_POST['peer_id'] > 2000000000) {
                $chat_id = (int)$_POST['peer_id'] - 2000000000;
            }
        }

        if ($chat_id <= 0) {
            $this->fail(100, "One of the parameters is missing: chat_id");
        }

        $peer_id = 2000000000 + $chat_id;

        // Fetch conversation members from openvk-im
        $response = $this->invoke("messages.getConversationMembers", [
            "peer_id"  => (string) $peer_id,
            "extended" => "0",
        ], $group_id);

        $items = $response['items'] ?? [];
        $invitedByMap = [];
        $userIds = [];

        foreach ($items as $item) {
            $mId = (int) ($item['member_id'] ?? 0);
            if ($mId > 0) {
                $userIds[] = $mId;
                $invitedByMap[$mId] = (int) ($item['invited_by'] ?? 0);
            }
        }

        // If no fields requested -> return flat array of user IDs
        if (empty($fields)) {
            return $userIds;
        }

        // If fields are requested -> fetch User objects and attach invited_by
        if (!empty($userIds)) {
            $apiUsers = (new APIUsers())->get(implode(',', $userIds), $fields, $name_case);
            $userList = [];
            foreach ($apiUsers as $u) {
                $uObj = is_array($u) ? (object) $u : $u;
                $uId = $uObj->id ?? 0;
                if (isset($invitedByMap[$uId])) {
                    $uObj->invited_by = $invitedByMap[$uId];
                }
                $userList[] = $uObj;
            }
            return $userList;
        }

        return [];
    }

    public function getConversationMembers(int $peer_id = 0, int $extended = 0, int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        if ($peer_id === 0) {
            $this->fail(100, "One of the parameters is missing: peer_id");
        }

        $params = [
            "peer_id"  => $peer_id,
            "extended" => $extended,
        ];

        $response = $this->invoke("messages.getConversationMembers", $params, $group_id);

        if ($extended) {
            $this->hydrateExtendedData($response, "photo_50,photo_100,photo_200,online,last_seen,sex,screen_name");
        }

        $res = [
            "count"    => (int)($response['count'] ?? 0),
            "items"    => $response['items'] ?? [],
            "profiles" => $response['profiles'] ?? [],
            "groups"   => $response['groups'] ?? [],
        ];

        if (!empty($response['chat_settings'])) {
            $chatSettings = $response['chat_settings'];
            if ($peer_id > 2000000000) {
                $chatId = $peer_id - 2000000000;
                $chatObj = (new ChatRepo())->getByChatId($chatId);
                if ($chatObj) {
                    $chatObj->setData($chatSettings);
                    $chatSettings = $chatObj->toChatSettingsStruct($this->getUser());
                }
            }
            $res['chat_settings'] = $chatSettings;
        }

        return $res;
    }

    public function getConversationsById(string $peer_ids = '', int $extended = 0, int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        if (empty($peer_ids)) {
            $peer_ids = $_GET['peer_ids'] ?? $_POST['peer_ids'] ?? '';
        }

        if (empty($peer_ids)) {
            $this->fail(100, "One of the parameters is missing: peer_ids");
        }

        $params = [
            "peer_ids" => $peer_ids,
            "extended" => $extended,
        ];

        $response = $this->invoke("messages.getConversationsById", $params, $group_id);

        $chatIds = [];
        if (!empty($response['items'])) {
            foreach ($response['items'] as $item) {
                $peer = $item['conversation']['peer'] ?? null;
                if ($peer && $peer['type'] === 'chat') {
                    $chatIds[] = (int) ($peer['id'] - 2000000000);
                }
            }
        }

        if ($extended && !empty($response['chats'])) {
            foreach ($response['chats'] as $chat) {
                $chatId = is_array($chat) ? ($chat['id'] ?? 0) : (int) $chat;
                if ($chatId > 2000000000) {
                    $chatIds[] = (int) ($chatId - 2000000000);
                }
            }
        }

        $chatIds = array_unique(array_filter($chatIds));
        $loadedChats = [];

        if (!empty($chatIds)) {
            $chatsRepo = new ChatRepo();
            foreach ($chatIds as $cId) {
                $chatObj = $chatsRepo->getByChatId($cId);
                $loadedChats[$cId] = $chatObj ?: null;
            }
        }

        $currentUserId = $this->getUser()->getId();
        if (!empty($response['items'])) {
            foreach ($response['items'] as &$item) {
                $conversation = &$item['conversation'];
                $peer = $conversation['peer'] ?? null;

                if ($peer && $peer['type'] === 'chat') {
                    $chatId = (int) ($peer['id'] - 2000000000);
                    $chatEntity = $loadedChats[$chatId] ?? null;

                    if (!$chatEntity) {
                        $chatsRepo = new ChatRepo();
                        $chatEntity = $chatsRepo->create($chatId, $this->getDefaultChatTitle($chatId));
                        $loadedChats[$chatId] = $chatEntity;
                    }

                    if ($chatEntity) {
                        if (!empty($conversation['chat_settings'])) {
                            $chatEntity->setData($conversation['chat_settings']);
                        }
                        $conversation['chat_settings'] = $chatEntity->toChatSettingsStruct($this->getUser());
                    }
                }

                if (!empty($conversation['chat_settings']['pinned_message']) && is_array($conversation['chat_settings']['pinned_message'])) {
                    $this->sanitizeMessageAttachmentsRecursive($conversation['chat_settings']['pinned_message']);
                }
                if (!empty($conversation['pinned_message']) && is_array($conversation['pinned_message'])) {
                    $this->sanitizeMessageAttachmentsRecursive($conversation['pinned_message']);
                }

                if (!empty($item['last_message']) && is_array($item['last_message'])) {
                    $this->sanitizeMessageAttachmentsRecursive($item['last_message']);
                }

                $this->enrichConversationCanWrite($conversation, $currentUserId);
            }
            unset($item);
        }

        if ($extended) {
            $this->hydrateExtendedData($response, "photo_200,online", $loadedChats);
        }

        return [
            "count"    => (int)($response['count'] ?? 0),
            "items"    => $response['items'] ?? [],
            "chats"    => $response['chats'] ?? [],
            "profiles" => $response['profiles'] ?? [],
            "groups"   => $response['groups'] ?? [],
        ];
    }

    // Это очень страшное кмк, стоит подумать над чем-то получше.
    public function searchConversations(string $q = '', int $extended = 0, int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        $q = trim($q);

        if (empty($q)) {
            $response = $this->invoke("messages.searchConversations", ["q" => "", "extended" => $extended], $group_id);
            return [
                "count"    => (int)($response['count'] ?? 0),
                "items"    => $response['items'] ?? [],
                "profiles" => $response['profiles'] ?? [],
                "groups"   => $response['groups'] ?? [],
            ];
        }

        $params = [
            "q"        => $q,
            "extended" => "1"
        ];
        $response = $this->invoke("messages.searchConversations", $params, $group_id);

        $items = $response['items'] ?? [];
        if (empty($items)) {
            return ["count" => 0, "items" => [], "profiles" => [], "groups" => []];
        }

        $userIdsToCheck = [];
        foreach ($items as $item) {
            $peer = $item['conversation']['peer'] ?? null;
            if ($peer && $peer['type'] === 'user') {
                $userIdsToCheck[] = (int)$peer['id'];
            }
        }

        $matchedUserIds = [];
        if (!empty($userIdsToCheck)) {
            $usersRepo = new USRRepo();

            $stream = $usersRepo->find($q);

            foreach ($stream as $user) {
                $userId = (int) $user->getId();

                if (in_array($userId, $userIdsToCheck, true)) {
                    $matchedUserIds[] = $userId;
                }
            }
        }

        $filteredItems = [];
        foreach ($items as $item) {
            $peer = $item['conversation']['peer'] ?? null;
            if (!$peer) continue;

            if ($peer['type'] === 'chat') {
                $filteredItems[] = $item;
            } elseif ($peer['type'] === 'user' && in_array((int)$peer['id'], $matchedUserIds, true)) {
                $filteredItems[] = $item;
            }
        }

        $currentUserId = $this->getUser()->getId();
        foreach ($filteredItems as &$item) {
            if (!empty($item['conversation']['chat_settings']['pinned_message']) && is_array($item['conversation']['chat_settings']['pinned_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($item['conversation']['chat_settings']['pinned_message']);
            }
            if (!empty($item['conversation']['pinned_message']) && is_array($item['conversation']['pinned_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($item['conversation']['pinned_message']);
            }
            if (!empty($item['last_message']) && is_array($item['last_message'])) {
                $this->sanitizeMessageAttachmentsRecursive($item['last_message']);
            }
            if (!empty($item['conversation'])) {
                $this->enrichConversationCanWrite($item['conversation'], $currentUserId);
            }
        }
        unset($item);

        if ($extended === 1) {
            $this->hydrateExtendedData($response);
        }

        return [
            "count"    => count($filteredItems),
            "items"    => $filteredItems,
            "profiles" => $response['profiles'] ?? [],
            "groups"   => $response['groups'] ?? [],
            "chats"    => $response['chats'] ?? [],
        ];
    }


    public function markAsImportantConversation(int $peer_id = 0, int $important = 1, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0) {
            $this->fail(100, "One of the parameters is missing: peer_id");
        }

        $params = [
            "peer_id"   => $peer_id,
            "important" => $important === 1 ? "1" : "0",
        ];

        $this->invoke("messages.markAsImportantConversation", $params, $group_id);

        return 1;
    }

    public function markAsAnsweredConversation(int $peer_id = 0, int $answered = 1, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0) {
            $this->fail(100, "One of the parameters is missing: peer_id");
        }

        $params = [
            "peer_id"  => $peer_id,
            "answered" => $answered === 1 ? "1" : "0",
        ];

        $this->invoke("messages.markAsAnsweredConversation", $params, $group_id);

        return 1;
    }

    public function deleteConversation(int $peer_id = 0, int $user_id = 0, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        $resolvedPeerId = $this->resolvePeer($user_id, $peer_id);
        if (is_null($resolvedPeerId) || $resolvedPeerId === 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id or user_id");
        }

        $params = [
            "peer_id" => (string) $resolvedPeerId,
        ];

        return (int) $this->invoke("messages.deleteConversation", $params, $group_id);
    }

    // Legacy alias: messages.deleteDialog (deprecated since 5.80, replaced by messages.deleteConversation)
    public function deleteDialog(int $user_id = 0, int $peer_id = 0, int $offset = 0, int $count = 0, int $group_id = 0, int $chat_id = 0): int
    {
        if ($chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
            $user_id = 0;
        }
        return $this->deleteConversation($peer_id, $user_id, $group_id);
    }

    // ----------------------------------
    //              History
    // ----------------------------------

    public function getHistory(
        int $offset = 0,
        int $count = 20,
        int $user_id = 0,
        int $peer_id = 0,
        int $chat_id = 0,
        int $report_id = 0,
        int $start_message_id = 0,
        int $rev = 0,
        int $extended = 0,
        int $preview_length = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ): array {
        $this->requireUser();

        $report = null;
        $resolvedPeerId = null;
        $data = null;

        if ($report_id != 0) {
            $canAccessHelpdesk = $this->getUser()->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\TicketReply')->whichBelongsTo(0);
            if (!$canAccessHelpdesk) {
                $this->fail(15, "Access denied");
            }

            $report = (new Reports)->get($report_id);

            if (!$report || $report->isDeleted()) {
                $this->fail(-50, "Report does not exist anymore");
            }

            $resolvedPeerId = $report->getContentObject(true)->getPeerId();
        } else {
            if ($user_id > 2000000000) {
                $chat_id = $user_id - 2000000000;
                $user_id = 0;
            } elseif ($peer_id > 2000000000) {
                $chat_id = $peer_id - 2000000000;
                $peer_id = 0;
            }

            $resolvedPeerId = $this->resolvePeer($user_id, $peer_id, $chat_id);
            if (is_null($resolvedPeerId) || $resolvedPeerId === 0) {
                $this->fail(100, "One of the parameters specified was missing or invalid: peer_id, user_id or chat_id");
            }
        }

        $params = [
            "offset"           => (string) $offset,
            "count"            => (string) min(abs($count), 200),
            "peer_id"          => (string) $resolvedPeerId,
            "start_message_id" => (string) $start_message_id,
            "rev"              => (string) $rev,
            "extended"         => (string) $extended,
            "preview_length"   => (string) $preview_length,
            "fields"           => $fields,
        ];

        if ($user_id > 0) {
            $params["user_id"] = (string) $user_id;
        }
        if ($chat_id > 0) {
            $params["chat_id"] = (string) $chat_id;
        }

        if ($report != null) {
            $data = $this->invoke("messages.getHistory", $params, $group_id, $report->authorId());
        } else {
            $data = $this->invoke("messages.getHistory", $params, $group_id);
        }

        if (!empty($data['items'])) {
            $isLegacy = (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR <= 5 && defined("VKAPI_DECL_VER_MINOR") && VKAPI_DECL_VER_MINOR < 80);
            $loadedChat = null;
            if ($isLegacy && $resolvedPeerId > 2000000000) {
                $localChatId = $resolvedPeerId - 2000000000;
                $loadedChat = (new ChatRepo())->getByChatId($localChatId);
            }

            foreach ($data['items'] as &$message) {
                $this->sanitizeMessageAttachmentsRecursive($message);

                $message['mid'] = (int) ($message['id'] ?? 0);
                if (!isset($message['from_id'])) {
                    $message['from_id'] = (int) ($message['user_id'] ?? 0);
                }
                $message['uid'] = (int) ($message['from_id'] ?? $message['user_id'] ?? 0);
                $message['body'] = (string) ($message['body'] ?? $message['text'] ?? "");
                $message['read_state'] = (int) ($message['read_state'] ?? 0);
                $message['date'] = (int) ($message['date'] ?? 0);

                if ($isLegacy && $loadedChat && !empty($message['chat_id'])) {
                    $chatStruct = $loadedChat->toChatSettingsStruct($this->getUser());
                    if (empty($message['title'])) {
                        $message['title'] = $chatStruct['title'] ?? $this->getDefaultChatTitle((int) $message['chat_id']);
                    }
                    if (empty($message['photo_50'])) {
                        $message['photo_50'] = $chatStruct['photo_50'] ?? "";
                        $message['photo_100'] = $chatStruct['photo_100'] ?? "";
                        $message['photo_200'] = $chatStruct['photo_200'] ?? "";
                    }
                }
            }
            unset($message);
        }

        if ($extended == 1) {
            if (!empty($data['items'])) {
                $extUserIDs = [];
                $extGroupIDs = [];
                foreach ($data['items'] as $item) {
                    $this->collectEntityIdsRecursive($item, $extUserIDs, $extGroupIDs);
                }
                if (!empty($extUserIDs)) {
                    $data['profiles'] = array_merge($data['profiles'] ?? [], $extUserIDs);
                }
                if (!empty($extGroupIDs)) {
                    $data['groups'] = array_merge($data['groups'] ?? [], $extGroupIDs);
                }
            }
            $this->hydrateExtendedData($data, $fields);
        }

        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            $items = $data['items'] ?? [];
            $count = (int) ($data['count'] ?? count($items));
            if ($count === 0 || empty($items)) {
                return [0];
            }
            return array_merge([$count], $items);
        }

        return $data;
    }

    public function getHistoryAttachments(
        int $peer_id = 0,
        string $media_type = "photo",
        string $start_from = "",
        int $count = 30,
        int $photo_sizes = 0,
        string $fields = "",
        int $extended = 0,
        int $group_id = 0,
        int $preserve_order = 0,
        int $max_forwards_level = 45,
        int $user_id = -1,
        int $chat_id = -1,
        string $domain = ""
    ): object {
        $this->requireUser();

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);
        if (!$resolvedId) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id is required");
        }

        $params = [
            "peer_id"            => (string) $resolvedId,
            "media_type"         => $media_type,
            "count"              => (string) $count,
            "preserve_order"     => (string) $preserve_order,
            "max_forwards_level" => (string) $max_forwards_level,
        ];

        if ($start_from !== "") {
            $params["start_from"] = $start_from;
        }
        if ($photo_sizes > 0) {
            $params["photo_sizes"] = (string) $photo_sizes;
        }

        $data = $this->invoke("messages.getHistoryAttachments", $params, $group_id);

        if (is_array($data) && !empty($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (!empty($item['attachment'])) {
                    if (is_string($item['attachment'])) {
                        $attWrap = [$item['attachment']];
                        $this->replaceAttachments($attWrap, ["gift", "doc", "audio_message", "link"]);
                        $type = '';
                        if (!empty($attWrap[0])) {
                            if (is_object($attWrap[0])) {
                                $type = $attWrap[0]->type ?? '';
                            } elseif (is_array($attWrap[0])) {
                                $type = $attWrap[0]['type'] ?? '';
                            }
                        }

                        if (!empty($attWrap[0]) && $type !== 'unknown' && $type !== '') {
                            $item['attachment'] = $attWrap[0];
                        } else {
                            $rawStr = $item['attachment'];
                            preg_match('/^[a-zA-Z_]+/', $rawStr, $m);
                            $rawType = $m[0] ?? 'unknown';
                            $item['attachment'] = [
                                "type" => $rawType,
                                $rawType => [
                                    "raw" => $rawStr
                                ]
                            ];
                        }
                    }
                }
            }
            unset($item);
        }

        if ($extended > 0 || !empty($fields)) {
            $this->hydrateExtendedData($data, $fields);
        }

        return (object) $data;
    }

    // ----------------------------------
    //              Status
    // ----------------------------------

    public function getLastActivity(int $user_id)
    {
        $uRepo = (new USRRepo());
        $u = $uRepo->get($user_id);

        if (empty($u)) {
            $this->fail(113, 'Unknown user id');
        }

        return (object) [
            "online" => (int) $u->isOnline(),
            "time"   => $u->getOnline()->timestamp(),
        ];
    }

    public function setActivity(
        int $user_id = 0,
        string $type = "typing",
        int $peer_id = 0,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (!in_array($type, ['typing', 'audiomessage'])) {
            $this->fail(100, "One of the parameters specified was missing or invalid: type");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id);

        if (!$resolvedId) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id is required");
        }

        $params = [
            "peer_id" => (string) $resolvedId,
            "type"    => $type,
        ];

        $this->invoke("messages.setActivity", $params, $group_id);

        return 1;
    }


    public function setChatPhoto(int $chat_id, string $file, string $hash): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $imagePath = (new Uploader())->getImagePath($file, $hash);

        if ($chat_id > 2000000000) {
            $chat_id = $chat_id - 2000000000;
        }

        $chatsRepo = new ChatRepo();
        $chat = $chatsRepo->getByChatId($chat_id);

        if (!$chat) {
            unlink($imagePath);
            $this->fail(14, "Chat not found");
        }

        if (!$chat->isMember($this->getUser())) {
            $this->fail(14, "Chat not found");
        }

        if (!$chat->canChangePhoto($this->getUser())) {
            $this->fail(15, "Access denied.");
        }

        $ava = null;

        try {
            $ava = $chat->updatePhoto($this->getUser(), $imagePath);
        } catch (ImageException | InvalidStateException $e) {
            unlink($imagePath);
            $this->fail(129, "Invalid image file");
        }

        $messageId = 0;
        if ($this->broker->isEnabled()) {
            try {
                $imRes = $this->invoke("messages.setChatPhoto", [
                    "peer_id" => (string) (2000000000 + $chat_id),
                ]);
                $messageId = (int) ($imRes['message_id'] ?? 0);
            } catch (\Throwable $e) {
                // If IM call fails, photo was still updated in DB
            }
        }

        return (object) [
            "message_id" => $messageId,
            "chat"       => $chat->toVkApiStruct($this->getUser()),
        ];
    }

    public function editChat(int $chat_id = 0, string $title = ""): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($chat_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: chat_id is required");
        }

        $title = trim($title);
        if (empty($title)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: title is required");
        }

        if ($chat_id > 2000000000) {
            $chat_id = $chat_id - 2000000000;
        }

        $chatsRepo = new ChatRepo();
        $chat = $chatsRepo->getByChatId($chat_id);

        if (!$chat || !$chat->isMember($this->getUser())) {
            $this->fail(14, "Chat not found");
        }

        $chat->setTitle($title);
        $chat->save();

        if ($this->broker->isEnabled()) {
            $this->invoke("messages.editChat", [
                "peer_id" => (string) (2000000000 + $chat_id),
                "title"   => $title,
            ]);
        }

        return 1;
    }

    public function deleteChatPhoto(int $chat_id = 0): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($chat_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: chat_id is required");
        }

        if ($chat_id > 2000000000) {
            $chat_id = $chat_id - 2000000000;
        }

        $chatsRepo = new ChatRepo();
        $chat = $chatsRepo->getByChatId($chat_id);

        if (!$chat || !$chat->isMember($this->getUser())) {
            $this->fail(14, "Chat not found");
        }

        if (!$chat->canChangePhoto($this->getUser())) {
            $this->fail(15, "Access denied.");
        }

        $chat->deleteCurrentPhoto();
        $chat->save();

        $messageId = 0;
        if ($this->broker->isEnabled()) {
            try {
                $imRes = $this->invoke("messages.deleteChatPhoto", [
                    "peer_id" => (string) (2000000000 + $chat_id),
                ]);
                $messageId = (int) ($imRes['message_id'] ?? 0);
            } catch (\Throwable $e) {
                // If IM call fails, photo was still deleted in DB
            }
        }

        return (object) [
            "message_id" => $messageId,
            "chat"       => $chat->toVkApiStruct($this->getUser()),
        ];
    }

    public function getInviteLink(
        int $peer_id = 0,
        int $reset = 0,
        int $group_id = 0,
        int $chat_id = 0,
        int $can_see_history = 0,
        int $for_topic = 0
    ): object {
        $this->requireUser();

        $resolvedId = $peer_id > 0 ? $peer_id : ($chat_id > 2000000000 ? $chat_id : 2000000000 + $chat_id);

        if ($resolvedId <= 2000000000) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id must be a group chat");
        }

        $params = [
            "peer_id" => $resolvedId,
            "reset"   => $reset,
        ];

        if ($can_see_history > 0 || $for_topic > 0) {
            $params["can_see_history"] = 1;
            $params["can_see_messages_before"] = 1;
        }

        $data = (object) $this->invoke("messages.getInviteLink", $params, $group_id);

        if (!empty($data->link)) {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $code = preg_match('/(?:join=|\/join\/|join\/|invite=)?([A-Za-z0-9_-]+)$/', (string) $data->link, $m) ? $m[1] : $data->link;
            $data->link = ovk_scheme(true) . $host . "/im?join=" . $code;
        }

        return $data;
    }
    
    public function getChatPreview(
        string $link = "",
        string $fields = "photo_50,photo_100,photo_200",
        int $group_id = 0
    ): object {
        $this->requireUser();

        if (empty($link)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: link is required");
        }

        $params = [
            "link"   => $link,
            "fields" => $fields,
        ];

        $data = $this->invoke("messages.getChatPreview", $params, $group_id);

        if (is_array($data)) {
            if (!empty($data['preview']['local_id'])) {
                $localChatId = (int) $data['preview']['local_id'];
                $chatsRepo = new ChatRepo();
                $chatEntity = $chatsRepo->getByChatId($localChatId);
                if ($chatEntity) {
                    $chatStruct = $chatEntity->toChatSettingsStruct($this->getUser());
                    $data['preview']['photo'] = [
                        "photo_50"  => $chatStruct['photo_50'] ?? "",
                        "photo_100" => $chatStruct['photo_100'] ?? "",
                        "photo_200" => $chatStruct['photo_200'] ?? "",
                    ];
                    if (!empty($chatStruct['title'])) {
                        $data['preview']['title'] = $chatStruct['title'];
                    }
                }
            }

            if (!empty($fields)) {
                $this->hydrateExtendedData($data, $fields);
            }
        }

        return (object) $data;
    }

    public function joinChatByInviteLink(
        string $link = "",
        int $group_id = 0
    ): object {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($link)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: link is required");
        }

        $params = [
            "link" => $link,
        ];

        $data = $this->invoke("messages.joinChatByInviteLink", $params, $group_id);
        return (object) $data;
    }

    public function allowMessagesFromGroup(int $group_id, string $key = ""): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubRepo())->get($group_id);
        $user = $this->getUser();

        if (!$club || !$club->canBeViewedBy($user)) {
            $this->fail(15, "Access denied");
        }

        $blacklist = new Blacklist($user);
        $blacklist->unban($club);

        return 1;
    }

    public function denyMessagesFromGroup(int $group_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubRepo())->get($group_id);
        $user = $this->getUser();

        if (!$club || !$club->canBeViewedBy($user)) {
            $this->fail(15, "Access denied");
        }

        $blacklist = new Blacklist($user);
        $blacklist->ban($club);

        return 1;
    }

    public function isMessagesFromGroupAllowed(int $group_id = 0, int $user_id = 0): object
    {
        $this->requireUser();

        if ($group_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: group_id is required");
        }

        if ($user_id <= 0) {
            $user_id = $this->getUser()->getId();
        }

        $club = (new ClubRepo())->get($group_id);
        if (!$club || !$club->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $targetUser = (new USRRepo())->get($user_id);
        if (!$targetUser || $targetUser->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: user not found");
        }

        $blacklist = new Blacklist($targetUser);
        $isAllowed = !$blacklist->isBanned($club);

        return (object) [
            "is_allowed" => $isAllowed ? 1 : 0,
        ];
    }

    // ----------------------------------
    //              Custom
    // ----------------------------------

    public function getChatAvatarHistory(int $chat_id)
    {
        $this->requireUser();

        if ($chat_id > 2000000000) {
            $chat_id = $chat_id - 2000000000;
        }

        $chatsRepo = new ChatRepo();
        $chat = $chatsRepo->getByChatId($chat_id);

        if (!$chat || !$chat->isMember($this->getUser())) {
            $this->fail(14, "Chat not found");
        }

        $photos = [];
        foreach ($chat->getAvatarsHistory() as $photo) {
            $photos[] = $photo->toVkApiStruct(true);
        }

        return (object) [
            "count" => sizeof($photos),
            "items" => array_reverse($photos),
        ];
    }

    public function joinChatByTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);
        if (!$topic || $topic->isChatAttached() == false) {
            $this->fail(15, "Access denied");
        }

        $club = $topic->getClub();
        if (!$club || $club->isBanned()) {
            $this->fail(15, "Access denied");
        }

        $chat = $topic->getChat();

        $params = [
            "link" => $link,
        ];

        $data = $this->invoke("messages.joinChatByInviteLink", $params, $group_id);
        return (object) $data;
    }

    public function getUnreadMessages(int $group_id = 0)
    {
        $this->requireUser();

        return $this->invoke("im.getUnreadMessages", [], $group_id);
    }

    public function getUnreadConversations(int $group_id = 0)
    {
        $this->requireUser();

        return $this->invoke("im.getUnreadConversations", [], $group_id);
    }

    public function getMe(int $group_id = 0)
    {
        $this->requireUser();

        return $this->invoke("im.getMe", [], $group_id);
    }

    // $date - timestamp or string date
    public function getNearestMessageForDate(string $date, int $peer_id = 0, int $user_id = -1, int $chat_id = -1, int $group_id = 0)
    {
        $this->requireUser();

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id);
        if ($resolvedId === 0 || is_null($resolvedId)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id, user_id or chat_id is missing");
        }

        $params = [
            "peer_id" => (string) $resolvedId,
            "date"    => $date,
        ];

        return $this->invoke("messages.getNearestMessageForDate", $params, $group_id);
    }

    public function report(int $peer_id, int $message_id, ?int $group_id = null, string $type = "spam", string $comment = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $my_id = ($group_id ? $this->getUser()->getRealId() : $group_id);
        if ($peer_id == $my_id) {
            $this->fail(12, "Can't report yourself.");
        }

        $params = [
            "peer_id" => $peer_id,
            "conversation_message_ids" => $message_id,
        ];

        if ($group_id) {
            $params["group_id"] = $group_id;
        }

        $res = $this->invoke("messages.getByConversationMessageId", $params);
        $global_id = null;

        if ($res["items"] && sizeof($res["items"]) < 1) {
            $this->fail(15, "Access denied");
        }

        $global_id = (int) ($res["items"][0]["id"] ?? $res["items"][0]["global_id"]);
        if (sizeof(iterator_to_array((new Reports())->getDuplicates("message", $global_id, null, $this->getUser()->getId()))) > 0) {
            return 1;
        }

        if ($my_id == $res["items"][0]["from_id"]) {
            $this->fail(12, "Can't report yourself.");
        }

        $report = new Report();
        $report->setUser_id($this->getUser()->getId());
        $report->setTarget_id($global_id);
        $report->setType("message");
        $report->setReason($comment);
        $report->setCreated(time());
        $report->save();

        return 1;
    }

    public function getMessageViewers(int $peer_id = 0, int $conversation_message_id = 0, int $message_id = 0, int $group_id = 0, int $extended = 0, string $fields = "photo_50,photo_100,online,last_seen,sex")
    {
        $this->requireUser();

        $params = [
            "peer_id" => $peer_id,
        ];
        if ($conversation_message_id > 0) {
            $params["conversation_message_id"] = $conversation_message_id;
        }
        if ($message_id > 0) {
            $params["message_id"] = $message_id;
        }

        $res = $this->invoke("im.getMessageViewers", $params, $group_id);

        if (!empty($res["user_ids"])) {
            $apiUsers = (new APIUsers())->get(implode(',', $res["user_ids"]), $fields);
            $res["profiles"] = $apiUsers;
        } else {
            $res["profiles"] = [];
        }

        return $res;
    }

    public function setChatModerator(int $peer_id = 0, int $user_id = 0, int $chat_id = 0, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id <= 2000000000 || $user_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id and user_id are required");
        }

        $params = [
            "peer_id" => $peer_id,
            "user_id" => $user_id,
        ];

        $res = $this->invoke("im.setChatModerator", $params, $group_id);
        return is_numeric($res) ? (int) $res : 1;
    }

    public function removeChatModerator(int $peer_id = 0, int $user_id = 0, int $chat_id = 0, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id <= 2000000000 || $user_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id and user_id are required");
        }

        $params = [
            "peer_id" => $peer_id,
            "user_id" => $user_id,
        ];

        $res = $this->invoke("im.removeChatModerator", $params, $group_id);
        return is_numeric($res) ? (int) $res : 1;
    }

    public function getChatModerators(int $peer_id = 0, int $chat_id = 0, int $extended = 0, string $fields = "photo_50,photo_100,online", int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id <= 2000000000) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id must be a group chat");
        }

        $params = [
            "peer_id" => $peer_id,
        ];

        $res = $this->invoke("im.getChatModerators", $params, $group_id);

        if ($extended && !empty($res["items"])) {
            $allIds = array_unique(array_filter(array_merge([$res["owner_id"] ?? 0], $res["items"] ?? [])));
            if (!empty($allIds)) {
                $apiUsers = (new APIUsers())->get(implode(',', $allIds), $fields);
                $res["profiles"] = $apiUsers;
            } else {
                $res["profiles"] = [];
            }
        }

        return $res;
    }

    public function setMemberRole(int $peer_id = 0, int $user_id = 0, int $member_id = 0, string $role = "", int $chat_id = 0, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($user_id <= 0 && $member_id > 0) {
            $user_id = $member_id;
        }

        if (empty($role)) {
            $role = (string) ($_GET['role'] ?? $_POST['role'] ?? '');
        }
        $role = strtolower(trim($role));

        if ($peer_id <= 2000000000 || $user_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id and user_id/member_id are required");
        }

        if ($role !== 'admin' && $role !== 'member') {
            $this->fail(100, "Invalid parameter: role must be 'admin' or 'member'");
        }

        $params = [
            "peer_id"   => $peer_id,
            "user_id"   => $user_id,
            "member_id" => $user_id,
            "role"      => $role,
        ];

        $res = $this->invoke("messages.setMemberRole", $params, $group_id);
        return is_numeric($res) ? (int) $res : 1;
    }

    public function setChatPermissions(
        int $peer_id = 0,
        int $chat_id = 0,
        string $permissions = "",
        string $invite = "",
        string $change_info = "",
        string $change_pin = "",
        string $use_mass_mentions = "",
        string $see_invite_link = "",
        string $change_invite_link = "",
        string $regenerate_link = "",
        string $call = "",
        string $change_admins = "",
        int $group_id = 0
    ): int {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 && $chat_id > 0) {
            $peer_id = 2000000000 + $chat_id;
        }

        if ($peer_id <= 2000000000) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id must be a group chat");
        }

        $params = [
            "peer_id" => $peer_id,
        ];

        if (!empty($permissions)) {
            $params["permissions"] = $permissions;
        }
        if (!empty($invite)) {
            $params["invite"] = $invite;
        }
        if (!empty($change_info)) {
            $params["change_info"] = $change_info;
        }
        if (!empty($change_pin)) {
            $params["change_pin"] = $change_pin;
        }
        if (!empty($use_mass_mentions)) {
            $params["use_mass_mentions"] = $use_mass_mentions;
        }
        if (!empty($see_invite_link)) {
            $params["see_invite_link"] = $see_invite_link;
        }
        if (!empty($change_invite_link)) {
            $params["change_invite_link"] = $change_invite_link;
        } elseif (!empty($regenerate_link)) {
            $params["change_invite_link"] = $regenerate_link;
        }
        if (!empty($call)) {
            $params["call"] = $call;
        }
        if (!empty($change_admins)) {
            $params["change_admins"] = $change_admins;
        }

        $res = $this->invoke("messages.setChatPermissions", $params, $group_id);
        return is_numeric($res) ? (int) $res : 1;
    }
}
