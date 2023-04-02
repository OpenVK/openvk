<?php declare(strict_types=1);
namespace openvk\Web\Util;
use Chandler\Patterns\TSimpleSingleton;

class Validator
{
    function emailValid(string $email): bool
    {
        if(empty($email)) return false;

        $email = trim($email);
        [$user, $domain] = explode("@", $email);
        if(is_null($domain)) return false;
        if(iconv_strlen($user) > 64) return false;
        $domain = idn_to_ascii($domain) . ".";

        return checkdnsrr($domain, "MX");
    }

    function telegramValid(string $telegram): bool
    {
        return (bool) preg_match("/^(?:t.me\/|@)?([a-zA-Z0-9_]{0,32})$/", $telegram);
    }

    function passwordStrong(string $password): bool{
        return (bool) preg_match("/^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z]).{8,}$/", $password);
    }

    use TSimpleSingleton;
}
