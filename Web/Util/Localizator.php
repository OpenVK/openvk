<?php declare(strict_types=1);
namespace openvk\Web\Util;
use Chandler\Patterns\TSimpleSingleton;

class Localizator
{
    const DEFAULT_LANG = "ru";
    
    private function __construct() {}
    
    protected function _getIncludes($string): array
    {
        $includes = [];
        $matches  = [];
        preg_match_all("%^#([A-z]++) <([A-z0-9_ -]+)>$%Xm", $string, $matches);
        for($i = 0; $i < sizeof($matches[1]); $i++) {
            $directive = $matches[1][$i];
            if($directive === "include") {
                $includes[] = dirname(__FILE__) . "/../../locales/" . $matches[2][$i] . ".strings";
            } else {
                trigger_error("Unknown preprocessor directive \"$directive\" in locale file, skipping.
                This will throw an error in a future version of Localizator::_getIncludes.", E_USER_DEPRECATED);
            }
        }
        
        return $includes;
    }
    
    protected function parse($file): array
    {
        $hash = sha1($file);
        if(isset($GLOBALS["localizationCache_$hash"])) return $GLOBALS["localizationCache_$hash"];
        
        $string = file_get_contents($file);
        $string = preg_replace("%^\%{.*\%}\r?$%m", "", $string); #Remove comments
        $array  = [];
        
        foreach(preg_split("%;[\\r\\n]++%", $string) as $statement) {
            $s = explode(" = ", trim($statement));
            
            try {
                $array[eval("return $s[0];")] = eval("return $s[1];");
            } catch(\ParseError $ex) {
                throw new \ParseError($ex->getMessage(). " near " . $s[0]);
            }
        }
        
        foreach(self::_getIncludes($string) as $include)
            $array = array_merge(@self::parse($include), $array);
        
        $GLOBALS["localizationCache_$hash"] = $array;
        return $array;
    }
    
    function _($id, $lang = NULL): string
    {
        $lang  = is_null($lang) ? static::DEFAULT_LANG : $lang;
        $array = @self::parse(dirname(__FILE__) . "/../../locales/$lang.strings");
        
        return $array[$id] ?? "@$id";
    }
    
    use TSimpleSingleton;
}
