<?php declare(strict_types=1);
namespace openvk\Web\Util;
use Zadarma_API\{Api as ZApi, ApiException as ZException};

class Sms
{
    function send(string $to, string $message): bool
    {
        $conf = (object) OPENVK_ROOT_CONF["openvk"]["credentials"]["zadarma"];
        if(!$conf->enable) return false;
        
        try {
            $api = new ZApi($conf->key, $conf->secret, false);
            $res = $api->sendSms($to, $message, $conf->callerId);
        } catch(ZException $e) {
            return false;
        }
        
        return true;
    }
}
