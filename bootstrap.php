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
        "imagick",
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
    $output    = $localizer->_($stringId, $lang);
    
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
            $output    = $newOutput === "@$numberedStringId" ? $output : $newOutput;
        }
        
        for($i = 0; $i < sizeof($variables); $i++)
            $output = preg_replace("%(?<!\\\\)(\\$)" . ($i + 1) . "%", $variables[$i], $output);
    }
    
    return $output;
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

return (function() {
    _ovk_check_environment();
    require __DIR__ . "/vendor/autoload.php";
    
    setlocale(LC_TIME, "POSIX");

    $showCommitHash = true; # plz remove when release
    if(is_dir($gitDir = OPENVK_ROOT . "/.git") && $showCommitHash)
        $ver = "nightly-" . `git --git-dir="$gitDir" log --pretty="%h" -n1 HEAD`;
    else
	$ver = "Build 15";

    define("OPENVK_VERSION", "Altair Preview ($ver)", false);
    define("OPENVK_DEFAULT_PER_PAGE", 10, false);
    define("__OPENVK_ERROR_CLOCK_IN_FUTURE", "Server clock error: FK1200-DTF", false);
});
