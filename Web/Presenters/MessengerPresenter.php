<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\Chats as ChatRepo;
use openvk\Web\Util\IMBroker;

final class MessengerPresenter extends OpenVKPresenter
{
    private $signaler;
    protected $presenterName = "messenger";

    public function renderIndex(): void
    {
        $joinCode = $_GET['join'] ?? null;
        if (empty($joinCode)) {
            $this->assertUserLoggedIn();
        }

        $im = IMBroker::i();
        $isAvailable = $im->isEnabled() && $im->pingLP();

        $this->template->imAvailable = $isAvailable;

        if (!empty($joinCode) && is_string($joinCode) && $isAvailable) {
            $this->loadChatPreviewMetadata($joinCode);
        }

        // #КакаоПрокакалось
    }

    private function loadChatPreviewMetadata(string $joinCode): void
    {
        $im = IMBroker::i();

        try {
            $raw = $im->invokeMethod(0, "messages.getChatPreview", ['link' => $joinCode]);
            if (empty($raw)) {
                return;
            }

            $res = json_decode($raw, true);
            $preview = $res['response']['preview'] ?? $res['preview'] ?? null;
            if (!$preview) {
                return;
            }

            $chatTitle = $preview['title'] ?? tr("chat");
            $membersCount = (int) ($preview['members_count'] ?? 0);
            $photoUrl = $preview['photo']['photo_200'] ?? $preview['photo']['photo_100'] ?? $preview['photo']['photo_50'] ?? null;

            if (!empty($preview['local_id'])) {
                $localChatId = (int) $preview['local_id'];
                $chatEntity = (new ChatRepo())->getByChatId($localChatId);
                if ($chatEntity) {
                    $userEntity = $this->user->identity ?? null;
                    $chatStruct = $chatEntity->toChatSettingsStruct($userEntity);
                    if (!empty($chatStruct['title'])) {
                        $chatTitle = $chatStruct['title'];
                    }
                    $photoUrl = $chatStruct['photo_200'] ?? $chatStruct['photo_100'] ?? $chatStruct['photo_50'] ?? $photoUrl;
                }
            }

            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $baseUrl = ovk_scheme(true) . $host;
            if (empty($photoUrl)) {
                $photoUrl = $baseUrl . "/assets/packages/static/openvk/img/camera_200.png";
            } elseif (!str_starts_with($photoUrl, "http://") && !str_starts_with($photoUrl, "https://")) {
                $photoUrl = $baseUrl . $photoUrl;
            }

            $desc = tr("chat_invite_preview_title") . " • " . tr("members_count", $membersCount);

            $this->template->chatPreview = [
                'chatTitle'    => $chatTitle,
                'membersCount' => $membersCount,
                'photoUrl'     => $photoUrl,
                'description'  => $desc,
                'joinUrl'      => $baseUrl . "/im?join=" . $joinCode,
            ];
        } catch (\Throwable $e) {
            // Ignore preview metadata fetch errors so page render doesn't break
        }
    }
}
