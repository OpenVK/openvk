<?php declare(strict_types=1);
namespace openvk\Web\Util;

class Sms
{
    static function send(string $to, string $message): bool
    {
        $conf = (object) OPENVK_ROOT_CONF["openvk"]["credentials"]["smsc"];
        if(!$conf->enable)
            return false;
        
        $args = http_build_query([
            "login"    => $conf->client,
            "psw"      => $conf->secret,
            "phones"   => $to,
            "mes"      => $message,
            "flash"    => 1,
            "translit" => 2,
            "fmt"      => 2,
        ]);
        
        $response = file_get_contents("https://smsc.ru/sys/send.php?$args");
        if(!$response)
            return false;
        
        $response = new \SimpleXMLElement($response);
        if(isset($response->error_code)) {
            trigger_error("Could not send SMS to $to: $response->error (Exception $response->error_code)", E_USER_WARNING);
            return false;
        }
        
        return true;
    }
}
