<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Repositories\{Posts, Comments};
use MessagePack\MessagePack;
use Chandler\Session\Session;

final class InternalAPIPresenter extends OpenVKPresenter
{
    private function fail(int $code, string $message): void
    {
        header("HTTP/1.1 400 Bad Request");
        header("Content-Type: application/x-msgpack");

        exit(MessagePack::pack([
            "brpc"  => 1,
            "error" => [
                "code"    => $code,
                "message" => $message,
            ],
            "id" => hexdec(hash("crc32b", (string) time())),
        ]));
    }

    private function succ($payload): void
    {
        header("Content-Type: application/x-msgpack");
        exit(MessagePack::pack([
            "brpc"   => 1,
            "result" => $payload,
            "id"     => hexdec(hash("crc32b", (string) time())),
        ]));
    }

    public function renderRoute(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("ты дебил это точка апи");
        }
        try {
            $input = (object) MessagePack::unpack(file_get_contents("php://input"));
        } catch (\Exception $ex) {
            $this->fail(-32700, "Parse error");
        }

        if (is_null($input->brpc ?? null) || is_null($input->method ?? null)) {
            $this->fail(-32600, "Invalid BIN-RPC");
        } elseif ($input->brpc !== 1) {
            $this->fail(-32610, "Invalid version");
        }

        $method = explode(".", $input->method);
        if (sizeof($method) !== 2) {
            $this->fail(-32601, "Procedure not found");
        }

        [$class, $method] = $method;
        $class = '\openvk\ServiceAPI\\' . $class;
        if (!class_exists($class)) {
            $this->fail(-32601, "Procedure not found");
        }

        $handler = new $class(is_null($this->user) ? null : $this->user->identity);
        if (!is_callable([$handler, $method])) {
            $this->fail(-32601, "Procedure not found");
        }

        try {
            $params = array_merge($input->params ?? [], [function ($data) {
                $this->succ($data);
            }, function (int $errno, string $errstr) {
                $this->fail($errno, $errstr);
            }]);
            $handler->{$method}(...$params);
        } catch (\TypeError $te) {
            $this->fail(-32602, "Invalid params");
        } catch (\Exception $ex) {
            $this->fail(-32603, "Uncaught " . get_class($ex));
        }
    }

    public function renderTimezone()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("ты дебил это метод апи");
        }
        $sessionOffset = Session::i()->get("_timezoneOffset");
        if (is_numeric($this->postParam("timezone", false))) {
            $postTZ = intval($this->postParam("timezone", false));
            if ($postTZ != $sessionOffset || $sessionOffset == null) {
                Session::i()->set("_timezoneOffset", $postTZ ? $postTZ : 3 * MINUTE);
                $this->returnJson([
                    "success" => 1, # If it's new value
                ]);
            } else {
                $this->returnJson([
                    "success" => 2, # If it's the same value (if for some reason server will call this func)
                ]);
            }
        } else {
            $this->returnJson([
                "success" => 0,
            ]);
        }
    }

    public function renderGetPhotosFromPost(int $owner_id, int $post_id)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            exit("иди нахуй заебал");
        }

        if ($this->postParam("parentType", false) == "post") {
            $post = (new Posts())->getPostById($owner_id, $post_id, true);
        } else {
            $post = (new Comments())->get($post_id);
        }


        if (is_null($post)) {
            $this->returnJson([
                "success" => 0,
            ]);
        } else {
            $response = [];
            $attachments = $post->getChildren();
            foreach ($attachments as $attachment) {
                if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                    $response[$attachment->getPrettyId()] = [
                        "url" => $attachment->getURLBySizeId('larger'),
                        "id"  => $attachment->getPrettyId(),
                    ];
                }
            }
            $this->returnJson([
                "success" => 1,
                "body" => $response,
            ]);
        }
    }

    public function renderGetPostTemplate(int $owner_id, int $post_id)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            $this->redirect("/");
        }

        $type = $this->queryParam("type", false);
        if ($type == "post") {
            $post = (new Posts())->getPostById($owner_id, $post_id, true);
        } else {
            $post = (new Comments())->get($post_id);
        }

        if (!$post || !$post->canBeEditedBy($this->user->identity)) {
            exit('');
        }

        header("Content-Type: text/plain");

        if ($type == 'post') {
            $this->template->_template = 'components/post.latte';
            $this->template->post = $post;
            $this->template->commentSection = $this->queryParam("from_page") == "another";
        } elseif ($type == 'comment') {
            $this->template->_template = 'components/comment.latte';
            $this->template->comment = $post;
        } else {
            exit('');
        }
    }

    public function renderImageFilter()
    {
        $is_enabled = OPENVK_ROOT_CONF["openvk"]["preferences"]["notes"]["disableHotlinking"] ?? true;
        $allowed_hosts = OPENVK_ROOT_CONF["openvk"]["preferences"]["notes"]["allowedHosts"] ?? [];

        $url = $this->requestParam("url");
        $url = base64_decode($url);

        if (!$is_enabled) {
            $this->redirect($url);
        }

        $url_parsed = parse_url($url);
        $host = $url_parsed['host'];

        if (in_array($host, $allowed_hosts)) {
            $this->redirect($url);
        } else {
            $this->redirect('/assets/packages/static/openvk/img/fn_placeholder.jpg');
        }
    }
}
