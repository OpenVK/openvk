<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Signaling\SignalManager;
use openvk\Web\Events\NewMessageEvent;
use openvk\Web\Models\Repositories\{Users, Clubs, Messages, Chats};
use openvk\Web\Models\Entities\{Message, Correspondence, Chat};

final class MessengerPresenter extends OpenVKPresenter
{
    private $messages;
    private $chats;
    private $signaler;
    protected $presenterName = "messenger";

    public function __construct(Messages $messages)
    {
        $this->messages = $messages;
        $this->chats = new Chats();
        $this->signaler = SignalManager::i();

        parent::__construct();
    }

    private function getCorrespondent(int $id): object
    {
        if ($id > 0) {
            return (new Users())->get($id);
        } elseif ($id < 0) {
            return (new Clubs())->get(abs($id));
        } elseif ($id === 0) {
            return $this->user->identity;
        }
    }

    public function renderIndex(): void
    {
        $this->assertUserLoggedIn();

        // #КакаоПрокакалось
    }

    public function renderEvents(int $randNum): void
    {
        $this->assertUserLoggedIn();

        header("Content-Type: application/json");
        $this->signaler->listen(function ($event, $id) {
            exit(json_encode([[
                "UUID"  => $id,
                "event" => $event->getLongPoolSummary(),
            ]]));
        }, $this->user->id);
    }

    public function renderVKEvents(int $id): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");

        if ($this->queryParam("act") !== "a_check") {
            header("HTTP/1.1 400 Bad Request");
            exit();
        } elseif (!$this->queryParam("key")) {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }

        $key       = $this->queryParam("key");
        $payload   = hex2bin(substr($key, 0, 16));
        $signature = hex2bin(substr($key, 16));
        if (($signature ^ (~CHANDLER_ROOT_CONF["security"]["secret"] | ((string) $id))) !== $payload) {
            exit(json_encode([
                "failed" => 3,
            ]));
        }

        $legacy = $this->queryParam("version") < 3;

        $time = intval($this->queryParam("wait"));

        if ($time > 60) {
            $time = 60;
        } elseif ($time == 0) {
            $time = 25;
        } // default

        $this->signaler->listen(function ($event, $eId) use ($id) {
            exit(json_encode([
                "ts"      => time(),
                "updates" => [
                    $event->getVKAPISummary($id),
                ],
            ]));
        }, $id, $time);
    }

    public function renderChat(int $id): void
    {
        $this->assertUserLoggedIn();

        $chat = $this->chats->get($id);
        if (!$chat) {
            $this->notFound();
        }

        // Check if user is in chat
        $userIds = array_map(fn($u) => $u->getId(), $chat->getUsers());
        if (!in_array($this->user->id, $userIds)) {
            $this->notFound();
        }

        $this->template->chat = $chat;
    }

    public function renderCreateChat(): void
    {
        $this->assertUserLoggedIn();

        if ($this->request->isMethod('POST')) {
            $title = $this->postParam('title');
            $userIds = $this->postParam('users'); // array of user ids

            if (!$title || empty($userIds)) {
                $this->flashFail("err", tr("error"), tr("chat_create_error"));
                return;
            }

            $users = array_merge([$this->user->id], $userIds);
            $chat = $this->chats->create('chat', $title, $this->user->id, $users);

            $this->redirect("/messenger/chat/" . $chat->getId());
        }
    }
}
