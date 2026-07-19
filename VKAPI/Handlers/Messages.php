<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Nette\InvalidStateException;
use Nette\Utils\ImageException;
use openvk\Web\Util\IMBroker;
use openvk\Web\Models\Repositories\{Users as USRRepo, Clubs as ClubRepo, Messages as MSGRepo, Chats as ChatRepo};
use openvk\Web\Models\Entities\{Photo, Correspondence, Message, Club as ClubEnt, Chat};
use openvk\VKAPI\Handlers\{Users as APIUsers, Groups as APIClubs};
use openvk\VKAPI\Utils\Uploader;

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
            // TODO: Add deleted field to group
            if (method_exists($peer, 'isDeleted') && $peer->isDeleted()) {
                $this->fail(18, "Recipient was deleted");
            }

            $senderId = $this->resolveSender($groupId);
            if ($peerId > 0 && $peerId < 2000000000 && $senderId !== $peerId) {
                if (method_exists($peer, 'getPrivacyPermission')) {
                    if (!$peer->getPrivacyPermission('messages.write', $this->getUser())) {
                        $this->fail(945, "This chat is disabled because of privacy settings");
                    }
                }
            }
        }
    }

    protected function invoke(string $method, array $params = [], int $group_id = 0)
    {
        $this->ensureBrokerActive();
        $sender_id = $this->resolveSender($group_id);

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

    protected function replaceAttachments(&$attachments)
    {
        if (empty($attachments)) {
            $attachments = [];
            return;
        }

        $parsed = parseAttachments($attachments, ['photo', 'video', 'audio', 'doc', 'poll']);
        $result = [];

        foreach ($parsed as $attachment) {
            if (!$attachment->canBeViewedBy($this->getUser())) {
                $result[] = [
                    "type"    => "unknown",
                    "unknown" => []
                ];

                continue;
            }

            $result[] = $attachment->toApiAttachment($this->getUser());
        }

        $attachments = $result;
    }

    /**
     * Обогащает сырые ID пользователей и групп полноценными объектами для VK API.
     * * @param array|object $payload Ссылка на данные от IM сервиса
     * @param string $fields Дополнительные поля для USRRepo
     */
    private function hydrateExtendedData(array &$payload, string $fields = "photo_200,online", array $loadedChats = []): void
    {
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
            foreach ($payload['chats'] as $chat) {
                $isArr = is_array($chat);
                $idVal = $isArr ? ($chat['id'] ?? 0) : (int) $chat;

                $globalChatId = abs($idVal);
                $localChatId = $globalChatId > 2000000000 ? ($globalChatId - 2000000000) : $globalChatId;

                if ($localChatId <= 0) {
                    continue;
                }

                #$chatEntity = (new ChatRepo())->getByChatId($localChatId);
                $chatEntity = $loadedChats[$localChatId];
                if ($chatEntity != null) {
                    $entry = $chatEntity->toVkApiStruct($this->getUser(), $chat);

                    $extendedChats[] = $entry;
                } else {
                    $extendedChats[] = array_merge([
                        'id'          => $globalChatId,
                        'type'        => 'chat',
                        'title'       => "ошибочная беседа",
                        'description' => "...",
                        'admin_id'    => 0,
                        'left'        => 0,
                        'kicked'      => 0
                    ], ["photo_50" => null]);
                }
            }
        }

        $payload['chats'] = $extendedChats;
    }

    // ----------------------------------
    //             Longpoll
    // ----------------------------------

    public function getLongPollHistory(int $ts = -1, int $pts = -1, int $preview_length = 0, int $events_limit = 1000, int $msgs_limit = 1000, int $group_id = 0): object
    {
        $this->requireUser();

        $params = [
            "ts"           => (string) $ts,
            "events_limit" => (string) $events_limit,
            "msgs_limit"   => (string) $msgs_limit,
            "version"      => "2",
        ];

        $data = $this->invoke("messages.getLongPollHistory", $params, $group_id);
        $result = (object) $data;

        $this->hydrateExtendedData($result);

        return $result;
    }

    public function getLongPollServer(int $need_pts = 1, int $version = 2, ?int $group_id = null): array
    {
        $this->requireUser();
        $baseUrl = $this->broker->getLongPollBaseUrl();

        if (!$this->broker->pingLP($baseUrl)) {
            $this->fail(500, "LongPoll server is unreachable. Check proxy settings for /nim endpoint.");
        }

        $params = [
            "version"  => (string) $version,
            "need_pts" => (string) $need_pts,
        ];

        if ($group_id > 0) {
            $params['group_id'] = (string) $group_id;
        }

        $data = $this->invoke("messages.getLongPollServer", $params, (int) $group_id);
        $data['server'] = $baseUrl;
        $data['unread_count'] = $this->getUser()->getUnreadMessagesCount();

        return $data;
    }

    // ----------------------------------
    //             Messages
    // ----------------------------------

    public function getById(string $message_ids, int $preview_length = 0, int $extended = 0): object
    {
        $this->requireUser();

        $params = [
            "message_ids"    => $message_ids,
            "extended"       => (string) $extended,
            "preview_length" => (string) $preview_length,
        ];

        $data = $this->invoke("messages.getById", $params);
        $result = (object) $data;

        if (!empty($result->items)) {
            foreach ($result->items as &$item) {
                if (isset($item['attachments'])) {
                    $this->replaceAttachments($item['attachments']);
                }
            }
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($result);
        }

        return $result;
    }

    public function send(
        int $user_id = -1,
        int $peer_id = 0,
        string $domain = "",
        int $chat_id = -1,
        int $group_id = 0,
        string $user_ids = "",
        string $message = "",
        int $sticker_id = -1,
        int $unnoticed = 0,
        string $attachment = "",
        int $random_id = 0,
        int $reply_to = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if (!empty($user_ids)) {
            $ids = preg_split("%, ?%", $user_ids);
            if (count($ids) > 100) {
                $this->fail(913, "Too many recipients");
            }

            $rIds = [];
            foreach ($ids as $id) {
                $rIds[] = $this->send(-1, (int) $id, "", -1, $group_id, "", $message, $sticker_id, 1, $attachment, rand(1, 2147483647));
            }
            return $rIds;
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);
        if (is_null($resolvedId)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: no recipient");
        }

        // TODO
        if ($sticker_id !== -1) {
            $this->fail(-151, "Stickers are not implemented");
        }

        $attachment_checked = parseAttachments($attachment);
        $attachment_secure = [];

        foreach ($attachment_checked as $item) {
            if (!$item->canBeViewedBy($this->getUser())) {
                continue;
            } else {
                $attachment_secure[] = $item->getAttachmentString();
            }
        }

        if (empty($message) && sizeof($attachment_secure) == 0) {
            $this->fail(100, "Message text is empty or invalid");
        }

        if ($unnoticed == 0) {
            $this->getUser()->updOnline($this->getPlatform());
        }

        $this->checkPeerAvailability($resolvedId, $group_id);

        # Finally we get to send a message!
        $params = [
            "peer_id"    => (string) $resolvedId,
            "message"    => $message,
            "attachment" => implode(",", $attachment_secure),
            "random_id"  => (string) ($random_id ?: rand(1, 2147483647)),
        ];

        if ($reply_to > 0) {
            $params["reply_to"] = (string) $reply_to;
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

        $attachment_checked = parseAttachments($attachment);
        $attachment_secure = [];

        foreach ($attachment_checked as $item) {
            if (!$item->canBeViewedBy($this->getUser())) {
                continue;
            } else {
                $attachment_secure[] = $item->getAttachmentString();
            }
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
        int $peer_id = 0,
        int $group_id = 0,
        string $domain = "",
        int $user_id = -1
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_ids is empty");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);
        if (is_null($resolvedId) || $resolvedId === 0) {
            $this->fail(936, "There is no peer with this id");
        }

        $params = [
            "peer_id"        => (string) $resolvedId,
            "message_ids"    => $message_ids,
            "delete_for_all" => (string) $delete_for_all,
        ];

        return $this->invoke("messages.delete", $params, $group_id);
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
        string $date = "",
        int $preview_length = 0,
        int $offset = 0,
        int $count = 20,
        int $extended = 0,
        int $group_id = 0
    ) {
        $this->requireUser();

        if (empty($q)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: q is empty");
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, -1, $domain);

        $params = [
            "q"              => $q,
            "count"          => (string) min(abs($count), 100),
            "offset"         => (string) abs($offset),
            "preview_length" => (string) max(0, $preview_length),
            "extended"       => $extended ? "1" : "0",
        ];

        if ($resolvedId !== 0) {
            $params["peer_id"] = (string) $resolvedId;
        }

        if (!empty($date)) {
            $params["date"] = $date; // DDMMYYYY
        }

        $data = $this->invoke("messages.search", $params, $group_id);

        if (!empty($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['attachments'])) {
                    $this->replaceAttachments($item['attachments']);
                }
            }
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($data);
        }

        return $data;
    }

    public function pin(
        int $peer_id = 0,
        int $message_id = 0,
        string $domain = "",
        int $user_id = -1,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

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

        return $this->invoke("messages.pin", $params, $group_id);
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
        int $extended = 0,
        int $group_id = 0
    ) {
        $this->requireUser();

        $params = [
            "count"    => (string) $count,
            "offset"   => (string) $offset,
            "extended" => (string) $extended,
        ];

        $data = $this->invoke("messages.getImportantMessages", $params, $group_id);

        if (!empty($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['attachments'])) {
                    $this->replaceAttachments($item['attachments']);
                }
            }
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($data);
        }

        return $data;
    }

    public function markAsImportant(
        string $message_ids = "",
        int $important = 1,
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_ids is empty");
        }

        $params = [
            "message_ids" => $message_ids,
            "important"   => (string) $important,
        ];

        return $this->invoke("messages.markAsImportant", $params, $group_id);
    }

    public function markAsRead(
        int $peer_id = 0,
        int $start_message_id = 0,
        string $message_ids = "",
        int $group_id = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($peer_id === 0 && empty($message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id or message_ids is required");
        }

        $params = [
            "peer_id"          => (string) $peer_id,
            "start_message_id" => (string) $start_message_id,
            "message_ids"      => $message_ids,
        ];

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
                if (isset($item['attachments'])) {
                    $this->replaceAttachments($item['attachments']);
                }
            }
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

        if (empty($title)) {
            $this->fail(100, "One of the parameters is missing: title");
        }

        if (empty($user_ids)) {
            $this->fail(100, "One of the parameters is missing: user_ids");
        }

        $rawIds = preg_split("%, ?%", $user_ids);
        $targetUserIds = array_filter(array_map('intval', $rawIds));
        $currentUser = $this->getUser();

        foreach ($targetUserIds as $id) {
            if ($id === $currentUser->getId()) {
                continue;
            }

            if (!$currentUser->isFriendsWith($id)) {
                $this->fail(15, "Access denied: user with ID " . $id . " is not your friend");
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

    public function addChatUser(int $peer_id = 0, string $user_id = "0", int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if ($peer_id === 0 || $user_id === 0) {
            $this->fail(100, "One of the parameters is missing: peer_id or user_id");
        }

        if ($peer_id < 2000000000) {
            $this->fail(15, "Access denied: cannot add user to direct message");
        }

        $rawIds = preg_split("%, ?%", $user_id);
        $targetUserIds = array_filter(array_map('intval', $rawIds));
        $currentUser = $this->getUser();

        foreach ($targetUserIds as $id) {
            if ($id === $currentUser->getId()) {
                continue;
            }

            if (!$currentUser->isFriendsWith($id)) {
                $this->fail(15, "Access denied: user with ID " . $id . " is not your friend");
            }
        }

        $params = [
            "peer_id" => $peer_id,
            "user_id" => $user_id,
        ];

        $this->invoke("messages.addChatUser", $params, $group_id);

        return 1;
    }

    public function removeChatUser(int $peer_id = 0, int $user_id = 0, int $group_id = 0): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

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
        string $fields = "photo200,online",
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

                if ($chatObj) {
                    $loadedChats[$cId] = $chatObj;
                    error_log("Chat $cId found. Title in DB is: '" . $chatObj->getTitle() . "'");
                } else {
                    $loadedChats[$cId] = null;
                    error_log("Chat $cId NOT FOUND in DB!");
                }
            }
        }

        foreach ($payload['items'] as &$item) {
            $conversation = &$item['conversation'];
            $peer = $conversation['peer'] ?? null;

            if ($peer && $peer['type'] === 'chat') {
                $chatId = (int) ($peer['id'] - 2000000000);
                $chatEntity = $loadedChats[$chatId] ?? null;

                $adminId = (int) ($conversation['chat_settings']['admin_id'] ?? 0);
                $isAdmin = ($adminId === $currentUserId && $currentUserId > 0);

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

                $conversation['chat_settings']['acl'] = array_merge($defaultAcl, $conversation['chat_settings']['acl'] ?? []);

                $title = "Беседа №" . $chatId;
                $photos = [
                    "photo_50"  => null,
                    "photo_100" => null,
                    "photo_200" => null,
                ];

                if ($chatEntity) {
                    try {
                        $title = $chatEntity->getTitle() ?: $title;
                        $photos = [
                            "photo_50"  => $chatEntity->getPhotoURL("miniscule"),
                            "photo_100" => $chatEntity->getPhotoURL("tiny"),
                            "photo_200" => $chatEntity->getPhotoURL("normal"),
                        ];
                    } catch (\Throwable $e) {
                    }
                }

                $conversation['chat_settings']['id'] = $peer['id'];
                $conversation['chat_settings']['title'] = $title;
                $conversation['chat_settings']['photo'] = $photos;
            }

            if (isset($item['last_message']['attachments'])) {
                $this->replaceAttachments($item['last_message']['attachments']);
            }
        }
        unset($item);

        if ($extended) {
            $this->hydrateExtendedData($payload, $fields, $loadedChats);
        }

        return $payload;
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
            $this->hydrateExtendedData($response);
        }

        return [
            "count"    => (int)($response['count'] ?? 0),
            "items"    => $response['items'] ?? [],
            "profiles" => $response['profiles'] ?? [],
            "groups"   => $response['groups'] ?? [],
        ];
    }

    public function getConversationsById($peer_ids = '', int $extended = 0, int $group_id = 0): array
    {
        $this->requireUser();
        $this->ensureBrokerActive();

        // это костыль, я не знаю почему peer_ids не передаётся в метод.
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

    // ----------------------------------
    //              History
    // ----------------------------------

    public function getHistory(
        int $offset = 0,
        int $count = 20,
        int $user_id = 0,
        int $peer_id = 0,
        int $chat_id = 0,
        int $start_message_id = 0,
        int $rev = 0,
        int $extended = 0,
        string $fields = "photo_200,online",
        int $group_id = 0
    ): array {
        $this->requireUser();

        $resolvedPeerId = $this->resolvePeer($user_id, $peer_id, $chat_id);

        $params = [
            "offset"           => (string) $offset,
            "count"            => (string) min(abs($count), 200),
            "peer_id"          => (string) $resolvedPeerId,
            "start_message_id" => (string) $start_message_id,
            "rev"              => (string) $rev,
            "extended"         => (string) $extended,
            "fields"           => $fields,
        ];

        $data = $this->invoke("messages.getHistory", $params, $group_id);

        if (!empty($data['items'])) {
            foreach ($data['items'] as &$message) {
                if (!empty($message['attachments'])) {
                    $this->replaceAttachments($message['attachments']);
                }
            }
        }

        if ($extended == 1) {
            $this->hydrateExtendedData($data, $fields);
        }

        return $data;
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


    public function setChatPhoto(string $file): object
    {
        $this->requireUser();

        $uploadData = json_decode($file, false);
        if (!$uploadData || !isset($uploadData->photo) || !isset($uploadData->hash)) {
            $this->fail(100, "Invalid file data");
        }

        $imagePath = (new Uploader())->getImagePath($uploadData->photo, $uploadData->hash, $uploader, $group);

        $peerId = (int) ($uploadData->peer_id ?? 0);
        if ($peerId < 2000000000) {
            unlink($imagePath);
            $this->fail(100, "Invalid peer_id: not a chat");
        }

        $chatId = $peerId - 2000000000;
        $chatsRepo = new ChatRepo();
        $chat = $chatsRepo->getByChatId($chatId);

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

        $chat->setPhotoId($photoObj->getId());
        $chat->save();

        return (object) [
            "message_id" => 0,
            "chat"       => $chat->toVkApiStruct($this->getUser()),
        ];
    }

    // ----------------------------------
    //              Custom
    // ----------------------------------

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
}
