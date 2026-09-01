<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use Nette\InvalidStateException;
use Nette\Utils\ImageException;
use openvk\Web\Util\IMBroker;
use openvk\Web\Models\Repositories\{Reports, Topics as TopicsRepo, Users as USRRepo, Clubs as ClubRepo, Messages as MSGRepo, Chats as ChatRepo};
use openvk\Web\Models\Entities\{Report, Photo, Message, Club as ClubEnt};
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
                            $this->fail(946, "This group blacklisted your account");
                        }
                    }
                }
            } else {
                $senderObj = $cRepo->get(abs($senderId));
            }

            if ($peerId > 0 && $peerId < 2000000000 && $senderId !== $peerId) {
                if ($senderId > 0 && method_exists($peer, 'getPrivacyPermission')) {
                    if (!$peer->getPrivacyPermission('messages.write', $senderObj)) {
                        $existence = $this->invoke("im.checkPeerExist", [
                            "peer_id" => $peerId
                        ]);

                        if (!$existence["exists"]) {
                            $this->fail(945, "This chat is disabled because of privacy settings");
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
                $strAttachments[] = $att;
            }
        }

        $result = [];
        if (!empty($strAttachments)) {
            $parsed = parseAttachments($strAttachments, array_merge(['photo', 'video', 'audio', 'doc', 'poll', 'wall'], $allowedAdditional));

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
                    $chatEntity = $chatsRepo->create($localChatId, "Chat " . $localChatId);
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
                if (isset($msg['attachments'])) {
                    $this->replaceAttachments($msg['attachments'], ["gift"]);
                }
            }
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
        $data['server'] = $baseUrl;

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

                if (!$isDeleted) {
                    if (!empty($msgObj['attachments'])) {
                        $this->replaceAttachments($msgObj['attachments'], ["gift"]);
                    } else {
                        $msgObj['attachments'] = [];
                    }

                    if (!empty($msgObj['fwd_messages'])) {
                        foreach ($msgObj['fwd_messages'] as &$fwd) {
                            if (!empty($fwd['attachments'])) {
                                $this->replaceAttachments($fwd['attachments'], ["gift"]);
                            } else {
                                $fwd['attachments'] = [];
                            }
                        }
                        unset($fwd);
                    }
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
                        $msgObj['title'] = !empty($rawTitle) ? $rawTitle : ($chatStruct['title'] ?? ("Chat " . $chatId));
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
                        $msgObj['title'] = !empty($rawTitle) ? $rawTitle : ("Chat " . $chatId);
                        $msgObj['admin_id'] = $rawAdmin;
                        $msgObj['users_count'] = $rawCount;
                        $msgObj['chat_active'] = $rawActive;
                    }
                }

                if ($preview_length > 0) {
                    $msgObj['body'] = ovk_truncate_words($msgObj['body'], $preview_length);
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
                        foreach ($item['fwd_messages'] as $fwd) {
                            if (!empty($fwd['user_id']) && $fwd['user_id'] > 0 && $fwd['user_id'] < 2000000000) {
                                $userIDs[] = (int) $fwd['user_id'];
                            }
                        }
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

        return $payload;
    }

    public function getById(string $message_ids, int $preview_length = 0, int $extended = 0, string $fields = "photo_200,online"): object
    {
        $this->requireUser();

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
                if (!empty($item['attachments'])) {
                    $this->replaceAttachments($item['attachments'], ["gift"]);
                } else {
                    $item['attachments'] = [];
                }

                if (!empty($item['fwd_messages'])) {
                    foreach ($item['fwd_messages'] as &$fwd) {
                        if (!empty($fwd['attachments'])) {
                            $this->replaceAttachments($fwd['attachments'], ["gift"]);
                        } else {
                            $fwd['attachments'] = [];
                        }
                    }
                    unset($fwd);
                }

                if (!empty($item['reply_message']['attachments'])) {
                    $this->replaceAttachments($item['reply_message']['attachments'], ["gift"]);
                }

                if ($isLegacy && !empty($item['chat_id'])) {
                    $cId = (int) $item['chat_id'];
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? ("Chat " . $cId);
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

        if ($extended == 1) {
            $this->hydrateExtendedData($data, $fields);
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
        string $forward = ""
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

        // Multi-peer send (5.80+)
        if (empty($peer_ids)) {
            $peer_ids = (string) ($_POST['peer_ids'] ?? $_GET['peer_ids'] ?? '');
        }

        if (!empty($peer_ids)) {
            $pIds = preg_split("%, ?%", $peer_ids);
            if (count($pIds) > 100) {
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

        // Multi-user send (5.20)
        if (!empty($user_ids)) {
            $ids = preg_split("%, ?%", $user_ids);
            if (count($ids) > 100) {
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

        // TODO
        if ($sticker_id !== -1) {
            $this->fail(-151, "Stickers are not implemented");
        }

        $attachment_checked = parseAttachments($attachment, ["photo", "video", "doc", "audio", "wall"]);
        $attachment_secure = [];

        foreach ($attachment_checked as $item) {
            if (!$item || !$item->canBeViewedBy($this->getUser())) {
                continue;
            } else {
                $attachment_secure[] = $item->getAttachmentString();
            }
        }

        if (empty($message) && sizeof($attachment_secure) == 0 && empty($forward_messages) && $reply_to <= 0) {
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
            "guid"       => (string) ($guid ?: $random_id),
        ];

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

        $attachment_checked = parseAttachments($attachment, ["photo", "video", "doc", "audio", "wall"]);
        $attachment_secure = [];

        foreach ($attachment_checked as $item) {
            if (!$item || !$item->canBeViewedBy($this->getUser())) {
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
                if (!empty($item['attachments'])) {
                    $this->replaceAttachments($item['attachments'], ["gift"]);
                } else {
                    $item['attachments'] = [];
                }

                if (!empty($item['fwd_messages'])) {
                    foreach ($item['fwd_messages'] as &$fwd) {
                        if (!empty($fwd['attachments'])) {
                            $this->replaceAttachments($fwd['attachments'], ["gift"]);
                        } else {
                            $fwd['attachments'] = [];
                        }
                    }
                    unset($fwd);
                }

                if (!empty($item['reply_message']['attachments'])) {
                    $this->replaceAttachments($item['reply_message']['attachments'], ["gift"]);
                }

                if ($isLegacy && !empty($item['chat_id'])) {
                    $cId = (int) $item['chat_id'];
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? ("Chat " . $cId);
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

        if ($extended == 1) {
            $this->hydrateExtendedData($data, $fields);
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
                if (!empty($item['attachments'])) {
                    $this->replaceAttachments($item['attachments'], ["gift"]);
                } else {
                    $item['attachments'] = [];
                }

                if (!empty($item['fwd_messages'])) {
                    foreach ($item['fwd_messages'] as &$fwd) {
                        if (!empty($fwd['attachments'])) {
                            $this->replaceAttachments($fwd['attachments'], ["gift"]);
                        } else {
                            $fwd['attachments'] = [];
                        }
                    }
                    unset($fwd);
                }

                if (!empty($item['reply_message']['attachments'])) {
                    $this->replaceAttachments($item['reply_message']['attachments'], ["gift"]);
                }

                if ($isLegacy && !empty($item['chat_id'])) {
                    $cId = (int) $item['chat_id'];
                    if (!isset($loadedChats[$cId])) {
                        $loadedChats[$cId] = $chatsRepo->getByChatId($cId);
                    }
                    $chatObj = $loadedChats[$cId];
                    if ($chatObj) {
                        $chatStruct = $chatObj->toChatSettingsStruct($this->getUser());
                        if (empty($item['title'])) {
                            $item['title'] = $chatStruct['title'] ?? ("Chat " . $cId);
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
                if (isset($item['attachments'])) {
                    $this->replaceAttachments($item['attachments'], ["gift"]);
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
                $chatEntity = $chatsRepo->create($localChatId, "Chat " . $localChatId);
            }

            if (isset($itemsMap[$localChatId])) {
                $chatEntity->setData($itemsMap[$localChatId]['chat_settings'] ?? []);
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

            if (!empty($fields) && !empty($chatStruct['users'])) {
                $hydratedUsers = [];
                foreach ($chatStruct['users'] as $uId) {
                    $uIdInt = (int) $uId;
                    if (isset($userProfilesMap[$uIdInt])) {
                        $hydratedUsers[] = $userProfilesMap[$uIdInt];
                    } else {
                        $hydratedUsers[] = $uIdInt;
                    }
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
                    $chatEntity = $chatsRepo->create($chatId, "Chat " . $chatId);
                    $loadedChats[$chatId] = $chatEntity;
                }

                if ($chatEntity) {
                    if (!empty($conversation["chat_settings"])) {
                        $chatEntity->setData($conversation["chat_settings"]);
                    }
                    $conversation['chat_settings'] = $chatEntity->toChatSettingsStruct($this->getUser());
                }
            }

            if (isset($conversation['chat_settings']['pinned_message'])) {
                if (!empty($conversation['chat_settings']['pinned_message']['attachments']) && is_array($conversation['chat_settings']['pinned_message']['attachments'])) {
                    $this->replaceAttachments($conversation['chat_settings']['pinned_message']['attachments'], ["gift"]);
                } else {
                    $conversation['chat_settings']['pinned_message']['attachments'] = [];
                }
            }
            if (isset($conversation['pinned_message'])) {
                if (!empty($conversation['pinned_message']['attachments']) && is_array($conversation['pinned_message']['attachments'])) {
                    $this->replaceAttachments($conversation['pinned_message']['attachments'], ["gift"]);
                } else {
                    $conversation['pinned_message']['attachments'] = [];
                }
            }

            if (isset($item['last_message']['attachments'])) {
                if (is_array($item['last_message']['attachments'])) {
                    $this->replaceAttachments($item['last_message']['attachments'], ["gift"]);
                } else {
                    $item['last_message']['attachments'] = [];
                }
            }
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
        int $group_id = 0
    ): array {
        $this->requireUser();

        $filter = $unread ? "unread" : "all";
        $params = [
            "offset"   => (string) $offset,
            "count"    => (string) min(abs($count), 200),
            "filter"   => $filter,
            "extended" => (string) $extended,
        ];

        $payload = $this->invoke("messages.getConversations", $params, $group_id);

        if (empty($payload['items'])) {
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

            if (!empty($msgObj['attachments'])) {
                $this->replaceAttachments($msgObj['attachments'], ["gift"]);
            } else {
                $msgObj['attachments'] = [];
            }

            if (!empty($msgObj['fwd_messages'])) {
                foreach ($msgObj['fwd_messages'] as &$fwd) {
                    if (!empty($fwd['attachments'])) {
                        $this->replaceAttachments($fwd['attachments'], ["gift"]);
                    } else {
                        $fwd['attachments'] = [];
                    }
                }
                unset($fwd);
            }

            if ($peerType === 'chat') {
                $localChatId = $peerId > 2000000000 ? ($peerId - 2000000000) : $peerId;
                $chatEntity = $loadedChats[$localChatId] ?? null;

                $msgObj['user_id'] = (int) ($lastMsg['from_id'] ?? $currentUserId);
                $msgObj['chat_id'] = $localChatId;

                $chatSettings = $conv['chat_settings'] ?? [];
                $members = $chatSettings['members'] ?? $chatSettings['users'] ?? [];

                if (!$chatEntity) {
                    $chatsRepo = new ChatRepo();
                    $chatEntity = $chatsRepo->create($localChatId, "Chat " . $localChatId);
                    $loadedChats[$localChatId] = $chatEntity;
                }

                if ($chatEntity) {
                    if (!empty($chatSettings)) {
                        $chatEntity->setData($chatSettings);
                    }
                    $chatStruct = $chatEntity->toChatSettingsStruct($this->getUser());
                    $msgObj['title'] = !empty($msgObj['title']) ? $msgObj['title'] : ($chatStruct['title'] ?? ("Chat " . $localChatId));
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
                    $msgObj['title'] = !empty($msgObj['title']) ? $msgObj['title'] : ("Chat " . $localChatId);
                    $msgObj['admin_id'] = (int) ($chatSettings['admin_id'] ?? 0);
                    $msgObj['users_count'] = count($members);
                    $msgObj['chat_active'] = array_slice($members, 0, 10);
                }
            } else {
                $msgObj['user_id'] = $peerId;
            }

            $flatMessages[] = $msgObj;
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
                        foreach ($item['fwd_messages'] as $fwd) {
                            if (!empty($fwd['user_id']) && $fwd['user_id'] > 0 && $fwd['user_id'] < 2000000000) {
                                $userIDs[] = (int) $fwd['user_id'];
                            }
                        }
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
                        $chatEntity = $chatsRepo->create($localChatId, "Chat " . $localChatId);
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
            $this->hydrateExtendedData($response, "photo_50,photo_100,photo_200,online,last_seen,sex");
        }

        return [
            "count"    => (int)($response['count'] ?? 0),
            "items"    => $response['items'] ?? [],
            "profiles" => $response['profiles'] ?? [],
            "groups"   => $response['groups'] ?? [],
        ];
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
                        $chatEntity = $chatsRepo->create($chatId, "Chat " . $chatId);
                        $loadedChats[$chatId] = $chatEntity;
                    }

                    if ($chatEntity) {
                        if (!empty($conversation['chat_settings'])) {
                            $chatEntity->setData($conversation['chat_settings']);
                        }
                        $conversation['chat_settings'] = $chatEntity->toChatSettingsStruct($this->getUser());
                    }
                }

                if (isset($conversation['chat_settings']['pinned_message'])) {
                    if (!empty($conversation['chat_settings']['pinned_message']['attachments']) && is_array($conversation['chat_settings']['pinned_message']['attachments'])) {
                        $this->replaceAttachments($conversation['chat_settings']['pinned_message']['attachments'], ["gift"]);
                    } else {
                        $conversation['chat_settings']['pinned_message']['attachments'] = [];
                    }
                }
                if (isset($conversation['pinned_message'])) {
                    if (!empty($conversation['pinned_message']['attachments']) && is_array($conversation['pinned_message']['attachments'])) {
                        $this->replaceAttachments($conversation['pinned_message']['attachments'], ["gift"]);
                    } else {
                        $conversation['pinned_message']['attachments'] = [];
                    }
                }

                if (isset($item['last_message']['attachments'])) {
                    if (is_array($item['last_message']['attachments'])) {
                        $this->replaceAttachments($item['last_message']['attachments'], ["gift"]);
                    } else {
                        $item['last_message']['attachments'] = [];
                    }
                }
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
    public function deleteDialog(int $user_id = 0, int $peer_id = 0, int $offset = 0, int $count = 0, int $group_id = 0): int
    {
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
            $resolvedPeerId = $this->resolvePeer($user_id, $peer_id, $chat_id);
            if (is_null($resolvedPeerId) || $resolvedPeerId === 0) {
                $this->fail(100, "One of the parameters specified was missing or invalid: peer_id, user_id or chat_id");
            }

            $this->checkPeerAvailability($resolvedPeerId, $group_id);
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
                if (!empty($message['attachments'])) {
                    $this->replaceAttachments($message['attachments'], ["gift"]);
                } else {
                    $message['attachments'] = [];
                }

                if (!empty($message['fwd_messages'])) {
                    foreach ($message['fwd_messages'] as &$fwd) {
                        if (!empty($fwd['attachments'])) {
                            $this->replaceAttachments($fwd['attachments'], ["gift"]);
                        } else {
                            $fwd['attachments'] = [];
                        }
                    }
                    unset($fwd);
                }

                if (!empty($message['reply_message']['attachments'])) {
                    $this->replaceAttachments($message['reply_message']['attachments'], ["gift"]);
                }

                if ($isLegacy && $loadedChat && !empty($message['chat_id'])) {
                    $chatStruct = $loadedChat->toChatSettingsStruct($this->getUser());
                    if (empty($message['title'])) {
                        $message['title'] = $chatStruct['title'] ?? ("Chat " . $message['chat_id']);
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
            $this->hydrateExtendedData($data, $fields);
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
            "items" => $photos,
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
        if (!$chat || !$chat->canJoin($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $res = $chat->join([$this->getUser()]);
        if (!$res) {
            $this->fail(4040404, "Not implemented");
        }

        return $chat->getId();
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

        $res = $this->invoke("messages.getMessageViewers", $params, $group_id);

        if (!empty($res["user_ids"])) {
            $apiUsers = (new APIUsers())->get(implode(',', $res["user_ids"]), $fields);
            $res["profiles"] = $apiUsers;
        } else {
            $res["profiles"] = [];
        }

        return $res;
    }
}
