<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use Latte\Engine as TemplatingEngine;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Notifications as N};
use openvk\Web\Util\NotificationBroker;
use Chandler\Session\Session;

class Notifications implements Handler
{
    protected $user;
    protected $notifs;

    public function __construct(?User $user)
    {
        $this->user  = $user;
        $this->notifs = new N();
    }

    public function fetch(callable $resolve, callable $reject): void
    {
        $notifConf = OPENVK_ROOT_CONF["openvk"]["credentials"]["notificationsBroker"];
        if (!($notifConf["enable"] ?? false)) {
            $reject(1999, "Disabled");
            return;
        }

        try {
            $broker = NotificationBroker::i();
            if (!$broker->isConnected()) {
                $reject(1998, "Redis connection error");
                return;
            };

            $userId = $this->user->getId();

            $session = Session::i();
            $lastId = $session->get("notifs_cursor");

            if (!$lastId) {
                $lastId = '0';
            }

            $events = $broker->getNew($userId, $lastId);

            if (empty($events)) {
                $reject(1983, "Nothing to report");
                return;
            }

            $event = end($events);
            $newCursor = $event['id'];
            $payload = (object) $event['data']['data'];
            
            $notification = $this->notifs->fromArray((array) $payload);

            if (!$notification) {
                $reject(1982, "Server Error");
                return;
            }

            $tplDir = __DIR__ . "/../Web/Presenters/templates/components/notifications/";
            $tplId  = "$tplDir$payload->actionCode/_$payload->originModelType" . "_" . $payload->targetModelType . "_.latte";
            $latte = new TemplatingEngine();
            $latte->setTempDirectory(CHANDLER_ROOT . "/tmp/cache/templates");
            $latte->addExtension(new \Latte\Essential\TranslatorExtension(tr(...)));

            $session->set("notifs_cursor", $newCursor);

            $userModel = $notification->getModel(1);

            $resolve([
                "title"    => tr("notif_" . $payload->actionCode . "_" . $payload->originModelType . "_" . $payload->targetModelType),
                "body"     => trim(preg_replace('%(\s){2,}%', "$1", $latte->renderToString($tplId, ["notification" => $notification]))),
                "ava"      => $userModel->getAvatarUrl(),
                "priority" => 1
            ]);

        } catch (\Exception $e) {
            $reject(1981, "Redis Error: " . $e->getMessage());
        }
    }
}
