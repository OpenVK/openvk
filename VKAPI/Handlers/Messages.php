<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Util\IMBroker;
use openvk\Web\Models\Repositories\{Users as USRRepo, Clubs as ClubRepo};
use openvk\Web\Models\Entities\{Club as ClubEnt};
use openvk\VKAPI\Handlers\{Users as APIUsers, Groups as APIClubs};

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
            $club = (new ClubRepo())->get((int)$group_id);
            
            if (!$club) {
                $this->fail(100, "One of the parameters specified was missing or invalid: group_id -> club not found");
            }

            if ($club->isBanned()) {
                $this->fail(15, "Access denied: this community is blocked");
            }

            if (!$club->canBeModifiedBy($this->getUser())) {
                $this->fail(15, "Access denied: you are not an administrator of this community");
            }

            $sender_id = ((int)$club->getId()) * -1;
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
            
            if (!$peerObj) return null;

            $id = (int)$peerObj->getId();
            return ($peerObj instanceof Club) ? -$id : $id;
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
            if (method_exists($peer, 'isBanned') && $peer->isBanned()) $this->fail(18, "Recipient is banned");
            if (method_exists($peer, 'isDeleted') && $peer->isDeleted()) $this->fail(18, "Recipient was deleted");
            
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
            $result[] = $attachment->toApiAttachment($this->getUser());
        }

        $attachments = $result;
    }

    /**
     * Обогащает сырые ID пользователей и групп полноценными объектами для VK API.
     * * @param array|object $payload Ссылка на данные от IM сервиса
     * @param string $fields Дополнительные поля для USRRepo
     */
    private function hydrateExtendedData(&$payload, string $fields = ""): void
    {
        $isObject = is_object($payload);
        $data = $isObject ? (array)$payload : $payload;
        
        if (!empty($data['profiles'])) {
            $userIDs = [];
            foreach ($data['profiles'] as $uData) {
                $userIDs[] = is_array($uData) ? ($uData['id'] ?? 0) : (int)$uData;
            }
            
            $userIDs = array_unique(array_filter($userIDs));

            if (!empty($userIDs)) {
                $apiUsers = new APIUsers();
                $data['profiles'] = $apiUsers->get(implode(',', $userIDs), $fields);
            }
        } else {
            $data['profiles'] = [];
        }

        if (!empty($data['groups'])) {
            $groupIDs = [];
            foreach ($data['groups'] as $gData) {
                $gid = is_array($gData) ? ($gData['id'] ?? 0) : abs((int)$gData);
                $groupIDs[] = $gid;
            }
            
            $groupIDs = array_unique(array_filter($groupIDs));

            if (!empty($groupIDs)) {
                $apiGroups = new APIClubs(); 
                $data['groups'] = $apiGroups->getById(implode(',', $groupIDs), "", $fields);
            }
        } else {
            $data['groups'] = [];
        }

        if ($isObject) {
            foreach ($data as $key => $value) {
                $payload->{$key} = $value;
            }
        } else {
            $payload = $data;
        }
    }

    // ----------------------------------
    //             Longpoll
    // ----------------------------------

    public function getLongPollHistory(int $ts = -1, int $pts = -1, int $preview_length = 0, int $events_limit = 1000, int $msgs_limit = 1000, int $group_id = 0): object
    {
        $this->requireUser();

        $params = [
            "ts"           => (string)$ts,
            "events_limit" => (string)$events_limit,
            "msgs_limit"   => (string)$msgs_limit,
            "version"      => "2"
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
            "version"  => (string)$version,
            "need_pts" => (string)$need_pts
        ];

        if ($group_id > 0) {
            $params['group_id'] = (string)$group_id;
        }

        $data = $this->invoke("messages.getLongPollServer", $params, (int)$group_id);
        $data['server'] = $baseUrl;

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
        int $forGodSakePleaseDoNotReportAboutMyOnlineActivity = 0,
        string $attachment = "",
        int $random_id = 0,
        int $reply_to = 0
    ) {
        $this->requireUser();
        $this->willExecuteWriteAction();
        $this->ensureBrokerActive();

        if (!empty($user_ids)) {
            $ids = preg_split("%, ?%", $user_ids);
            if (count($ids) > 100) $this->fail(913, "Too many recipients");

            $rIds = [];
            foreach ($ids as $id) {
                $rIds[] = $this->send(-1, (int)$id, "", -1, $group_id, "", $message, $sticker_id, 1, $attachment, rand(1, 2147483647));
            }
            return $rIds;
        }

        $resolvedId = $this->resolvePeer($user_id, $peer_id, $chat_id, $domain);
        if (is_null($resolvedId)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: no recipient");
        }

        // TODO
        if ($sticker_id !== -1) $this->fail(-151, "Stickers are not implemented");
        if (empty($message) && empty($attachment)) $this->fail(100, "Message text is empty or invalid");

        if ($forGodSakePleaseDoNotReportAboutMyOnlineActivity == 0) {
            $this->getUser()->updOnline($this->getPlatform());
        }

        $this->checkPeerAvailability($resolvedId, $group_id);

        # Finally we get to send a message!
        $params = [
            "peer_id"    => (string) $resolvedId,
            "message"    => $message,
            "attachment" => $attachment,
            "random_id"  => (string) ($random_id ?: rand(1, 2147483647)),
        ];

        if ($reply_to > 0) $params["reply_to"] = (string) $reply_to;

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

        if (empty($message) && empty($attachment)) {
            $this->fail(100, "Empty messages are not allowed");
        }

        $params = [
            "peer_id"               => (string) $resolvedId,
            "message_id"            => (string) $message_id,
            "message"               => $message,
            "attachment"            => $attachment,
            "keep_forward_messages" => (string) $keep_forward_messages
        ];

        $result = $this->invoke("messages.edit", $params, $group_id);

        return (int) $result;
    }

    public function delete(string $message_ids = "", int $delete_for_all = 0, int $group_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_ids is empty");
        }

        $params = [
            "message_ids"    => $message_ids,
            "delete_for_all" => (string)$delete_for_all
        ];

        return $this->invoke("messages.delete", $params, $group_id);
    }

    public function restore(int $message_id = 0, int $group_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($message_id <= 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: message_id is required");
        }

        $params = [
            "message_id" => (string)$message_id
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
            "count"          => (string)min(abs($count), 100),
            "offset"         => (string)abs($offset),
            "preview_length" => (string)max(0, $preview_length),
            "extended"       => $extended ? "1" : "0"
        ];

        if ($resolvedId !== 0) {
            $params["peer_id"] = (string)$resolvedId;
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
            "peer_id"    => (string)$resolvedId,
            "message_id" => (string)$message_id
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
            "peer_id" => (string)$resolvedId
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
            "count"    => (string)$count,
            "offset"   => (string)$offset,
            "extended" => (string)$extended
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
            "important"   => (string)$important
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
            "peer_id"          => (string)$peer_id,
            "start_message_id" => (string)$start_message_id,
            "message_ids"      => $message_ids
        ];

        $this->invoke("messages.markAsRead", $params, $group_id);

        return 1;
    }

    public function getByConversationMessageID(
        int $peer_id = 0,
        string $conversation_message_ids = "",
        int $extended = 0,
        string $fields = "",
        int $group_id = 0
    ) {
        $this->requireUser();

        if ($peer_id === 0 || empty($conversation_message_ids)) {
            $this->fail(100, "One of the parameters specified was missing or invalid: peer_id or conversation_message_ids is empty");
        }

        $params = [
            "peer_id"                  => (string)$peer_id,
            "conversation_message_ids" => $conversation_message_ids,
            "extended"                 => (string)$extended,
            "fields"                   => $fields
        ];

        $data = $this->invoke("messages.getByConversationMessageID", $params, $group_id);

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

    public function getConversations(
        int $offset = 0,
        int $count = 20,
        string $filter = "all",
        int $extended = 0,
        string $fields = "",
        int $group_id = 0
    ): array {
        $this->requireUser();

        $params = [
            "offset"   => (string)$offset,
            "count"    => (string)$count,
            "filter"   => $filter,
            "extended" => (string)$extended,
        ];

        $payload = $this->invoke("messages.getConversations", $params, $group_id);

        if (empty($payload['items'])) {
            return $payload;
        }

        foreach ($payload['items'] as &$item) {
            $conversation = &$item['conversation'];
            $peer = $conversation['peer'] ?? null;

            if ($peer && $peer['type'] === 'chat') {
                $settings = $conversation['chat_settings'] ?? [];

                $defaultAcl = [
                    "can_invite"             => true,
                    "can_change_info"        => false,
                    "can_change_pin"         => false,
                    "can_promote_users"      => false,
                    "can_see_invite_link"    => false,
                    "can_change_invite_link" => false,
                    "can_moderate"           => false,
                    "can_copy_chat"          => false
                ];

                $conversation['chat_settings']['acl'] = array_merge($defaultAcl, $settings['acl'] ?? []);

                if (empty($conversation['chat_settings']['title'])) {
                    $chatId = $peer['id'] - 2000000000;
                    $conversation['chat_settings']['title'] = "Беседа №" . $chatId;
                }
            }

            if (isset($item['last_message']['attachments'])) {
                $this->replaceAttachments($item['last_message']['attachments']);
            }
        }

        if ($extended) {
            $this->hydrateExtendedData($payload, $fields);
        }

        return $payload;
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
        string $fields = "",
        int $group_id = 0
    ): array {
        $this->requireUser();

        $resolvedPeerId = $this->resolvePeer($user_id, $peer_id, $chat_id);

        $params = [
            "offset"           => (string)$offset,
            "count"            => (string)min(abs($count), 200),
            "peer_id"          => (string)$resolvedPeerId,
            "start_message_id" => (string)$start_message_id,
            "rev"              => (string)$rev,
            "extended"         => (string)$extended,
            "fields"           => $fields
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

    public function getLastActivity(int $user_id) {
        $uRepo = (new USRRepo());
        $u = $uRepo->get($user_id);

        if (empty($u)) {
            $this->fail(113, 'Unknown user id');
        }

        return (object) [
            "online" => (int)$u->isOnline(),
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
            "peer_id" => (string)$resolvedId,
            "type"    => $type,
        ];

        $this->invoke("messages.setActivity", $params, $group_id);

        return 1;
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

    /*
    public function delete(string $message_ids, int $spam = 0, int $delete_for_all = 0): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $msgs  = new MSGRepo();
        $ids   = preg_split("%, ?%", $message_ids);
        $items = [];
        foreach ($ids as $id) {
            $message = $msgs->get((int) $id);
            if (!$message || $message->getSender()->getId() !== $this->getUser()->getId() && $message->getRecipient()->getId() !== $this->getUser()->getId()) {
                $items[$id] = 0;
            }

            $message->delete();
            $items[$id] = 1;
        }

        return (object) $items;
    }

    public function restore(int $message_id): int
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $msg = (new MSGRepo())->get($message_id);
        if (!$msg) {
            return 0;
        } elseif ($msg->getSender()->getId() !== $this->getUser()->getId()) {
            return 0;
        }

        $msg->undelete();
        return 1;
    }

    public function getConversations(int $offset = 0, int $count = 20, string $filter = "all", int $extended = 0, string $fields = ""): object
    {
        $this->requireUser();

        $convos = (new MSGRepo())->getCorrespondencies($this->getUser(), -1, $count, $offset);
        $convosCount = (new MSGRepo())->getCorrespondenciesCount($this->getUser());
        $list   = [];

        $users = [];
        foreach ($convos as $convo) {
            $correspondents = $convo->getCorrespondents();
            if ($correspondents[0]->getId() === $this->getUser()->getId()) {
                $peer = $correspondents[1];
            } else {
                $peer = $correspondents[0];
            }

            $lastMessage = $convo->getPreviewMessage();

            $listConvo = new APIConvo();
            $listConvo->peer = [
                "id"       => $peer->getId(),
                "type"     => "user",
                "local_id" => $peer->getId(),
            ];

            $canWrite = $peer->getSubscriptionStatus($this->getUser()) === 3;
            $listConvo->can_write = [
                "allowed" => $canWrite,
            ];

            $lastMessagePreview = null;
            if (!is_null($lastMessage)) {
                $listConvo->last_message_id = $lastMessage->getId();

                if ($lastMessage->getSender()->getId() === $this->getUser()->getId()) {
                    $author = $lastMessage->getRecipient()->getId();
                } else {
                    $author = $lastMessage->getSender()->getId();
                }

                $lastMessagePreview             = new APIMsg();
                $lastMessagePreview->id         = $lastMessage->getId();
                $lastMessagePreview->user_id    = $author;
                $lastMessagePreview->from_id    = $lastMessage->getSender()->getId();
                $lastMessagePreview->date       = $lastMessage->getSendTime()->timestamp();
                $lastMessagePreview->read_state = 1;
                $lastMessagePreview->out        = (int) ($lastMessage->getSender()->getId() === $this->getUser()->getId());
                $lastMessagePreview->body       = $lastMessage->getText(false);
                $lastMessagePreview->text       = $lastMessage->getText(false);
                $lastMessagePreview->emoji      = true;

                if ($extended == 1) {
                    $users[] = $author;
                }
            }

            $list[] = [
                "conversation" => $listConvo,
                "last_message" => $lastMessagePreview,
            ];
        }

        if ($extended == 0) {
            return (object) [
                "count" => $convosCount,
                "items" => $list,
            ];
        } else {
            $users[] = $this->getUser()->getId();
            $users = array_unique($users);

            return (object) [
                "count"    => $convosCount,
                "items"    => $list,
                "profiles" => (!empty($users) ? (new APIUsers())->get(implode(',', $users), $fields, 0, $count + 1) : []),
            ];
        }
    }

    public function getConversationsById(string $peer_ids, int $extended = 0, string $fields = "")
    {
        $this->requireUser();

        $peers = explode(',', $peer_ids);

        $output = [
            "count" => 0,
            "items" => [],
        ];

        $userslist = [];

        foreach ($peers as $peer) {
            if (key($peers) > 100) {
                continue;
            }

            if (is_null($user_id = $this->resolvePeer((int) $peer))) {
                $this->fail(-151, "Chats are not implemented");
            }

            $user     = (new USRRepo())->get((int) $peer);

            if ($user) {
                $dialogue = new Correspondence($this->getUser(), $user);
                $iterator = $dialogue->getMessages(Correspondence::CAP_BEHAVIOUR_START_MESSAGE_ID, 0, 1, 0, false);
                $msg      = $iterator[0]->unwrap(); // шоб удобнее было
                $output['items'][] = [
                    "peer" => [
                        "id" => $user->getId(),
                        "type" => "user",
                        "local_id" => $user->getId(),
                    ],
                    "last_message_id" => $msg->id,
                    "in_read" => $msg->id,
                    "out_read" => $msg->id,
                    "sort_id" => [
                        "major_id" => 0,
                        "minor_id" => $msg->id, // КОНЕЧНО ЖЕ
                    ],
                    "last_conversation_message_id" => $user->getId(),
                    "in_read_cmid" => $user->getId(),
                    "out_read_cmid" => $user->getId(),
                    "is_marked_unread" => $iterator[0]->isUnread(),
                    "important" => false, // целестора когда релиз
                    "can_write" => [
                        "allowed" => ($user->getId() === $this->getUser()->getId() || $user->getPrivacyPermission('messages.write', $this->getUser()) === true),
                    ],
                ];
                $userslist[] = $user->getId();
            }
        }

        if ($extended == 1) {
            $userslist          = array_unique($userslist);
            $output['profiles'] = (!empty($userslist) ? (new APIUsers())->get(implode(',', $userslist), $fields) : []);
        }

        $output['count'] = sizeof($output['items']);
        return (object) $output;
    }

    public function getHistory(int $offset = 0, int $count = 20, int $user_id = -1, int $peer_id = -1, int $start_message_id = 0, int $rev = 0, int $extended = 0, string $fields = ""): object
    {
        $this->requireUser();

        if (is_null($user_id = $this->resolvePeer($user_id, $peer_id))) {
            $this->fail(-151, "Chats are not implemented");
        }

        $peer = (new USRRepo())->get($user_id);
        if (!$peer) {
            $this->fail(1, "ошибка про то что пира нет");
        }

        $results  = [];
        $dialogue = new Correspondence($this->getUser(), $peer);
        $iterator = $dialogue->getMessages(Correspondence::CAP_BEHAVIOUR_START_MESSAGE_ID, $start_message_id, $count, abs($offset), $rev === 1);
        foreach ($iterator as $message) {
            $msgU = $message->unwrap(); # Why? As of OpenVK 2 Public Preview Two database layer doesn't work correctly and refuses to cache entities.
            # UPDATE: the issue seems to be caused by debug mode and json_encode (bruh_encode). ~~Dorothy

            $rMsg = new APIMsg();
            $rMsg->id         = $msgU->id;
            $rMsg->user_id    = $msgU->sender_id === $this->getUser()->getId() ? $msgU->recipient_id : $msgU->sender_id;
            $rMsg->from_id    = $msgU->sender_id;
            $rMsg->date       = $msgU->created;
            $rMsg->read_state = 1;
            $rMsg->out        = (int) ($msgU->sender_id === $this->getUser()->getId());
            $rMsg->body       = $message->getText(false);
            $rMsg->text       = $message->getText(false);
            $rMsg->emoji      = true;

            $results[] = $rMsg;
        }

        $output = [
            "count" => sizeof($results),
            "items" => $results,
        ];

        if ($extended == 1) {
            $users[] = $this->getUser()->getId();
            $users[] = $user_id;
            $output["profiles"] = (!empty($users) ? (new APIUsers($this->getUser()))->get(implode(',', $users), $fields) : []);
        }

        return (object) $output;
    }

    public function getLongPollHistory(int $ts = -1, int $preview_length = 0, int $events_limit = 1000, int $msgs_limit = 1000): object
    {
        $this->requireUser();

        $res = [
            "history"  => [],
            "messages" => [],
            "profiles" => [],
            "new_pts"  => 0,
        ];

        $manager = SignalManager::i();
        $events  = $manager->getHistoryFor($this->getUser()->getId(), $ts === -1 ? null : $ts, min($events_limit, $msgs_limit));
        foreach ($events as $event) {
            if (!($event instanceof NewMessageEvent)) {
                continue;
            }

            $message = $this->getById((string) $event->getLongPoolSummary()->message["uuid"], $preview_length, 1)->items[0];
            if (!$message) {
                continue;
            }

            $res["messages"][] = $message;
            $res["history"][]  = $event->getVKAPISummary($this->getUser()->getId());
        }

        $res["messages"] = [
            "count" => sizeof($res["messages"]),
            "items" => $res["messages"],
        ];
        return (object) $res;
    }

    public function getLongPollServer(int $need_pts = 1, int $lp_version = 3, ?int $group_id = null): array
    {
        $this->requireUser();

        if ($group_id > 0) {
            $this->fail(-151, "Not implemented");
        }

        $url = "http" . (ovk_is_ssl() ? "s" : "") . "://$_SERVER[HTTP_HOST]/nim" . $this->getUser()->getId();
        $key = openssl_random_pseudo_bytes(8);
        $key = bin2hex($key) . bin2hex($key ^ (~CHANDLER_ROOT_CONF["security"]["secret"] | ((string) $this->getUser()->getId())));
        $res = [
            "key"    => $key,
            "server" => $url,
            "ts"     => time(),
        ];

        if ($need_pts === 1) {
            $res["pts"] = -1;
        }

        return $res;
    }

    public function edit(int $message_id, string $message = "", string $attachment = "", int $peer_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $msg = (new MSGRepo())->get($message_id);

        if (empty($message) && empty($attachment)) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        if (!$msg || $msg->isDeleted()) {
            $this->fail(102, "Invalid message");
        }

        if ($msg->getSender()->getId() != $this->getUser()->getId()) {
            $this->fail(15, "Access to message denied");
        }

        if (!empty($message)) {
            $msg->setContent($message);
        }

        $msg->setEdited(time());
        $msg->save(true);

        if (!empty($attachment)) {
            $attachs = parseAttachments($attachment);
            $newAttachmentsCount = sizeof($attachs);

            $postsAttachments = iterator_to_array($msg->getChildren());

            if (sizeof($postsAttachments) >= 10) {
                $this->fail(15, "Message have too many attachments");
            }

            if (($newAttachmentsCount + sizeof($postsAttachments)) > 10) {
                $this->fail(158, "Message will have too many attachments");
            }

            foreach ($attachs as $attach) {
                if ($attach && !$attach->isDeleted() && $attach->getOwner()->getId() == $this->getUser()->getId()) {
                    $msg->attach($attach);
                } else {
                    $this->fail(52, "One of the attachments is invalid");
                }
            }
        }

        return 1;
    }
        */
}
