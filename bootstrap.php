<?php declare(strict_types=1);
use Chandler\Database\DatabaseConnection;
use Chandler\Session\Session;
use openvk\Web\Util\Localizator;
use openvk\Web\Util\Bitmask;

function _ovk_check_environment(): void
{
    $problems = [];
    if(file_exists(__DIR__ . "/update.pid"))
        $problems[] = "OpenVK is updating";
    
    if(!version_compare(PHP_VERSION, "7.3.0", ">="))
        $problems[] = "Incompatible PHP version: " . PHP_VERSION . " (7.3+ required, 7.4+ recommended)";
    
    if(!is_dir(__DIR__ . "/vendor"))
        $problems[] = "Composer dependencies missing";
    
    $requiredExtensions = [
        "gd",
        "fileinfo",
        "PDO",
        "pdo_mysql",
        "pcre",
        "hash",
        "curl",
        "Core",
        "iconv",
        "mbstring",
        "sodium",
        "openssl",
        "json",
        "tokenizer",
        "libxml",
        "date",
        "session",
        "SPL",
    ];
    if(sizeof($missingExtensions = array_diff($requiredExtensions, get_loaded_extensions())) > 0)
        foreach($missingExtensions as $extension)
            $problems[] = "Missing extension $extension";
    
    if(sizeof($problems) > 0) {
        require __DIR__ . "/misc/install_err.phtml";
        exit;
    }
}

function ovkGetQuirk(string $quirk): int
{
    static $quirks = NULL;
    if(!$quirks)
        $quirks = chandler_parse_yaml(__DIR__ . "/quirks.yml");
    
    return !is_null($v = $quirks[$quirk]) ? (int) $v : 0;
}

function ovk_proc_strtr(string $string, int $length = 0): string
{
    $newString = iconv_substr($string, 0, $length);
    
    return $newString . ($string !== $newString ? "…" : ""); #if cut hasn't happened, don't append "..."
}

function bmask(int $input, array $options = []): Bitmask
{
    return new Bitmask($input, $options["length"] ?? 1, $options["mappings"] ?? []);
}

function tr(string $stringId, ...$variables): string
{
    $localizer = Localizator::i();
    $lang      = Session::i()->get("lang", "ru");
    if($stringId === "__lang")
        return $lang;
    
    $output = $localizer->_($stringId, $lang);
    if(sizeof($variables) > 0) {
        if(gettype($variables[0]) === "integer") {
            $numberedStringId = NULL;
            $cardinal         = $variables[0];
            switch($cardinal) {
                case 0:
                    $numberedStringId = $stringId . "_zero";
                break;
                case 1:
                    $numberedStringId = $stringId . "_one";
                break;
                default:
                    $numberedStringId = $stringId . ($cardinal < 5 ? "_few" : "_other");
            }
            
            $newOutput = $localizer->_($numberedStringId, $lang);
            if($newOutput === "@$numberedStringId") {
                $newOutput = $localizer->_($stringId . "_other", $lang);
                if($newOutput === ("@" . $stringId . "_other"))
                    $newOutput = $output;
            }

            $output = $newOutput;
        }
        
        for($i = 0; $i < sizeof($variables); $i++)
            $output = preg_replace("%(?<!\\\\)(\\$)" . ($i + 1) . "%", $variables[$i], $output);
    }
    
    return $output;
}

function setLanguage($lg): void
{
    if (isLanguageAvailable($lg))
        Session::i()->set("lang", $lg);
    else
        trigger_error("The language '$lg' is not available", E_USER_NOTICE);
}

function getLanguage(): string
{
    return Session::i()->get("lang", "ru");
}

function getLanguages(): array
{
    return chandler_parse_yaml(OPENVK_ROOT . "/locales/list.yml")['list'];
}

function isLanguageAvailable($lg): bool
{
    $lg_temp = false;
    foreach(getLanguages() as $lang) {
        if ($lang['code'] == $lg) $lg_temp = true;
    }
    return $lg_temp;
}

function getBrowsersLanguage(): array
{
    if ($_SERVER['HTTP_ACCEPT_LANGUAGE'] != null) return mb_split(",", mb_split(";", $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]);
    else return array();
}

function eventdb(): ?DatabaseConnection
{
    $conf = OPENVK_ROOT_CONF["openvk"]["credentials"]["eventDB"];
    if(!$conf["enable"])
        return null;
    
    $db = (object) $conf["database"];
    return DatabaseConnection::connect([
        "dsn"      => $db->dsn,
        "user"     => $db->user,
        "password" => $db->password,
        "caching"  => [
            "folder" => __DIR__ . "/tmp",
        ],
    ]);
}

#NOTICE: invalid name, kept for compatability
function ovk_proc_strtrim(string $string, int $length = 0): string
{
    trigger_error("ovk_proc_strtrim is deprecated, please use fully compatible ovk_proc_strtr.", E_USER_DEPRECATED);
    
    return ovk_proc_strtr($string, $length);
}

function ovk_strftime_safe(string $format, ?int $timestamp = NULL): string
{
    $str = strftime($format, $timestamp ?? time());
    if(PHP_SHLIB_SUFFIX === "dll") {
        $enc = tr("__WinEncoding");
        if($enc === "@__WinEncoding")
            $enc = "Windows-1251";
        
        $nStr = iconv($enc, "UTF-8", $str);
        if(!is_null($nStr))
            $str = $nStr;
    }
    
    return $str;
}

function ovk_is_ssl(): bool
{
    if(!isset($GLOBALS["requestIsSSL"])) {
        $GLOBALS["requestIsSSL"] = false;
        
        if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
            $GLOBALS["requestIsSSL"] = true;
        } else {
            $forwardedProto = $_SERVER["HTTP_X_FORWARDED_PROTO"] ?? ($_SERVER["HTTP_X_FORWARDED_PROTOCOL"] ?? ($_SERVER["HTTP_X_URL_SCHEME"] ?? ""));
            if($forwardedProto === "https")
                $GLOBALS["requestIsSSL"] = true;
            else if(($_SERVER["HTTP_X_FORWARDED_SSL"] ?? "") === "on")
                $GLOBALS["requestIsSSL"] = true;
        }
    }
    
    return $GLOBALS["requestIsSSL"];
}

function ovk_scheme(bool $with_slashes = false): string
{
    $scheme = ovk_is_ssl() ? "https" : "http";
    if($with_slashes)
        $scheme .= "://";
    
    return $scheme;
}

return (function() {
    _ovk_check_environment();
    require __DIR__ . "/vendor/autoload.php";

    setlocale(LC_TIME, "POSIX");

    // TODO: Default language in config
    if(Session::i()->get("lang") == null) {
        $languages = array_reverse(getBrowsersLanguage());
        foreach($languages as $lg) {
            if(isLanguageAvailable($lg)) setLanguage($lg);    
        }
    }
    
    if(empty($_SERVER["REQUEST_SCHEME"]))
        $_SERVER["REQUEST_SCHEME"] = empty($_SERVER["HTTPS"]) ? "HTTP" : "HTTPS";

    $showCommitHash = true; # plz remove when release
    if(is_dir($gitDir = OPENVK_ROOT . "/.git") && $showCommitHash)
        $ver = trim(`git --git-dir="$gitDir" log --pretty="%h" -n1 HEAD` ?? "Unknown version") . "-nightly";
    else
        $ver = "Build 15";

    // Unix time constants
    define('MINUTE', 60);
    define('HOUR', 60 * MINUTE);
    define('DAY', 24 * HOUR);
    define('WEEK', 7 * DAY);
    define('MONTH', 30 * DAY);
    define('YEAR', 365 * DAY);

    define("nullptr", NULL);
    define("OPENVK_DEFAULT_INSTANCE_NAME", "OpenVK", false);
    define("OPENVK_VERSION", "Altair Preview ($ver)", false);
    define("OPENVK_DEFAULT_PER_PAGE", 10, false);
    define("__OPENVK_ERROR_CLOCK_IN_FUTURE", "Server clock error: FK1200-DTF", false);
});
