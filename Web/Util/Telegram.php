<?php declare(strict_types=1);
namespace openvk\Web\Util;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;

class Telegram
{
    static function send(string $to, string $text, bool $webPagePreview = false): bool
    {
        $conf = (object) OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"];
        if(!$conf->enable)
            return false;

        try {
            (new GuzzleClient)->request(
                "POST",
                "https://api.telegram.org/bot{$conf->token}/sendMessage",
                [
                    "form_params" => [
                        "chat_id" => $to,
                        "text" => $text,
                        "disable_web_page_preview" => $webPagePreview ? "true" : "false",
                        "parse_mode" => "HTML",
                    ]
                ]
            );
        } catch (GuzzleClientException $ex) {
            trigger_error("Could not send Telegram message to $to: {$ex->getMessage()}", E_USER_WARNING);
            return false;
        }

        return true;
    }
}
