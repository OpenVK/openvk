<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Chandler\Patterns\TSimpleSingleton;

class Localizator
{
    use TSimpleSingleton;
    public const DEFAULT_LANG = "ru";

    private function __construct() {}

    protected function _getIncludes($string): array
    {
        $includes = [];
        $matches  = [];
        preg_match_all("%^#([A-z]++) <([A-z0-9_ -]+)>$%Xm", $string, $matches);
        for ($i = 0; $i < sizeof($matches[1]); $i++) {
            $directive = $matches[1][$i];
            if ($directive === "include") {
                $includes[] = dirname(__FILE__) . "/../../locales/" . $matches[2][$i] . ".strings";
            } else {
                trigger_error("Unknown preprocessor directive \"$directive\" in locale file, skipping.
                This will throw an error in a future version of Localizator::_getIncludes.", E_USER_DEPRECATED);
            }
        }

        return $includes;
    }

    /*
     * parsing takes a LOT of cpu time. so for future visits,
     * we're parsing the locale and put it to native php file
     */
    protected function parse($file): array
    {
        $hash = md5($file);
        $array = [];

        if (isset($GLOBALS["localizationCache_$hash"])) {
            return $GLOBALS["localizationCache_$hash"];
        }

        $tmpDir = dirname(__FILE__) . "/../../tmp/locales/";
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0o777, true);
        }

        $cacheFile = $tmpDir . $hash . '.tmplocale.php';
        if (file_exists($cacheFile)) {
            $array = require $cacheFile;
            if (filemtime($file) == $array['__originalModifyDate']) {
                return $array;
            } else {
                $array = []; // run that back
            }
        }

        $string = file_get_contents($file);

        if (!$string) {
            return [];
        }

        foreach (preg_split("%;[\\r\\n]++%", $string) as $statement) {
            if ($statement == "") {
                continue;
            }

            $s = [];
            preg_match('/\"(.+)\" = \"(.+)\"/', trim($statement), $s);

            try {
                if (count($s) == 3) {
                    $array[$s[1]] = $s[2];
                }
            } catch (\ParseError $ex) {
                throw new \ParseError($ex->getMessage() . " near " . $s[0]);
            }
        }

        foreach (self::_getIncludes($string) as $include) {
            $array = array_merge(@self::parse($include), $array);
        }

        $array['__originalModifyDate'] = filemtime($file);
        $arrayExport = var_export($array, true);
        $tmpContent = '<?php return ' . $arrayExport . '; ?>';
        file_put_contents($cacheFile, $tmpContent);
        $GLOBALS["localizationCache_$hash"] = $array;
        return $array;
    }

    public function _($id, $lang = null): string
    {
        $lang  = is_null($lang) ? static::DEFAULT_LANG : $lang;
        $array = @self::parse(dirname(__FILE__) . "/../../locales/$lang.strings");

        return $array[$id] ?? "@$id";
    }

    public function export($lang = null): ?array
    {
        $lang  = is_null($lang) ? static::DEFAULT_LANG : $lang;
        $array = @self::parse(dirname(__FILE__) . "/../../locales/$lang.strings");

        return $array;
    }
}
