<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Util\IMBroker;

final class Queue extends VKAPIRequestHandler
{
    public function subscribe(string $queue_id = "", string $queue_ids = "", int $ts = 0): object
    {
        $this->requireUser();

        $scheme = ((($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https") || ovk_is_ssl()) ? "https://" : "http://";
        $baseUrl = preg_replace("~^https?://~i", $scheme, str_replace("/nim", "/queue", IMBroker::i()->getLongPollBaseUrl()), 1);
        $now     = time();

        $ids = [];
        foreach ([$queue_id, $queue_ids] as $raw) {
            if ($raw === "") {
                continue;
            }
            foreach (explode(",", $raw) as $one) {
                $one = trim($one);
                if ($one !== "") {
                    $ids[] = $one;
                }
            }
        }

        if (empty($ids)) {
            $ids = ["im" . $this->getUser()->getId()];
        }

        $queues = [];
        foreach ($ids as $qid) {
            $queues[] = (object) [
                "queue_id"  => $qid,
                "id"        => $qid,
                "base_url"  => $baseUrl,
                "name"      => $qid,
                "key"       => bin2hex(random_bytes(16)),
                "ts"        => (string) ($ts > 0 ? $ts : $now),
                "timestamp" => ($ts > 0 ? $ts : $now),
                "wait"      => 25,
                "events"    => [],
            ];
        }

        return (object) [
            "base_url" => $baseUrl,
            "queues"   => $queues,
        ];
    }

    public function unsubscribe(string $queue_id = "", string $queue_ids = ""): int
    {
        $this->requireUser();

        return 1;
    }
}
