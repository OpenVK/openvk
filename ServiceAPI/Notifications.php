<?php

declare(strict_types=1);

namespace openvk\ServiceAPI;

use Latte\Engine as TemplatingEngine;
use RdKafka\{Conf as RDKConf, KafkaConsumer};
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\{Notifications as N};

class Notifications implements Handler
{
    protected $user;
    protected $notifs;

    public function __construct(?User $user)
    {
        $this->user  = $user;
        $this->notifs = new N();
    }

    public function ack(callable $resolve, callable $reject): void
    {
        $this->user->updateNotificationOffset();
        $this->user->save();
        $resolve("OK");
    }

    public function fetch(callable $resolve, callable $reject): void
    {
        $kafkaConf = OPENVK_ROOT_CONF["openvk"]["credentials"]["notificationsBroker"];
        if (!$kafkaConf["enable"]) {
            $reject(1999, "Disabled");
            return;
        }

        $kafkaConf = $kafkaConf["kafka"];
        $conf = new RDKConf();
        $conf->set("metadata.broker.list", $kafkaConf["addr"] . ":" . $kafkaConf["port"]);
        $conf->set("group.id", "UserFetch-" . $this->user->getId()); # Чтобы уведы приходили только на разные устройства одного чебупелика
        $conf->set("auto.offset.reset", "latest");

        set_time_limit(30);
        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([ $kafkaConf["topic"] ]);

        while (true) {
            $message = $consumer->consume(30 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $descriptor = $message->payload;
                    [,$user,]   = explode(",", $descriptor);
                    if (((int) $user) === $this->user->getId()) {
                        $data         = (object) [];
                        $notification = $this->notifs->fromDescriptor($descriptor, $data);
                        if (!$notification) {
                            $reject(1982, "Server Error");
                            return;
                        }

                        $tplDir = __DIR__ . "/../Web/Presenters/templates/components/notifications/";
                        $tplId  = "$tplDir$data->actionCode/_$data->originModelType" . "_" . $data->targetModelType . "_.xml";
                        $latte  = new TemplatingEngine();
                        $latte->setTempDirectory(CHANDLER_ROOT . "/tmp/cache/templates");
                        $latte->addFilter("translate", fn($trId) => tr($trId));
                        $resolve([
                            "title"    => tr("notif_" . $data->actionCode . "_" . $data->originModelType . "_" . $data->targetModelType),
                            "body"     => trim(preg_replace('%(\s){2,}%', "$1", $latte->renderToString($tplId, ["notification" => $notification]))),
                            "ava"      => $notification->getModel(1)->getAvatarUrl(),
                            "priority" => 1,
                        ]);
                        return;
                    }

                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $reject(1983, "Nothing to report");
                    break 2;
                default:
                    $reject(1981, "Kafka Error: " . $message->errstr());
                    break 2;
            }
        }
    }
}
