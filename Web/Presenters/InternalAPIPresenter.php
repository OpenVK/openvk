<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use MessagePack\MessagePack;

final class InternalAPIPresenter extends OpenVKPresenter
{
    private function fail(int $code, string $message): void
    {
        header("HTTP/1.1 400 Bad Request");
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
        exit(MessagePack::pack([
            "brpc"   => 1,
            "result" => $payload,
            "id"     => hexdec(hash("crc32b", (string) time())),
        ]));
    }
    
    function renderRoute(): void
    {
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            exit("ты дебил это точка апи");
        
        try {
            $input = (object) MessagePack::unpack(file_get_contents("php://input"));
        } catch (\Exception $ex) {
            $this->fail(-32700, "Parse error");
        }
        
        if(is_null($input->brpc ?? NULL) || is_null($input->method ?? NULL))
            $this->fail(-32600, "Invalid BIN-RPC");
        else if($input->brpc !== 1)
            $this->fail(-32610, "Invalid version");
        
        $method = explode(".", $input->method);
        if(sizeof($method) !== 2)
            $this->fail(-32601, "Procedure not found");
        
        [$class, $method] = $method;
        $class = '\openvk\ServiceAPI\\' . $class;
        if(!class_exists($class))
            $this->fail(-32601, "Procedure not found");
        
        $handler = new $class(is_null($this->user) ? NULL : $this->user->identity);
        if(!is_callable([$handler, $method]))
            $this->fail(-32601, "Procedure not found");
        
        try {
            $params = array_merge($input->params ?? [], [function($data) {
                $this->succ($data);
            }, function($data) {
                $this->fail($data);
            }]);
            $handler->{$method}(...$params);
        } catch(\TypeError $te) {
            $this->fail(-32602, "Invalid params");
        } catch(\Exception $ex) {
            $this->fail(-32603, "Uncaught " . get_class($ex));
        }
    }
}