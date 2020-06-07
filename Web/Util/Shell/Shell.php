<?php declare(strict_types=1);
namespace openvk\Web\Util\Shell;

class Shell
{
    static function shellAvailable(): bool
    {
        if(ini_get("safe_mode"))
            return FALSE;
        
        $functions = array_map(function($x) {
            return trim($x);
        }, explode(" ", ini_get("disable_functions")));
        return !in_array("system", $functions);
    }
    
    static function commandAvailable(string $name): bool
    {
        if(!Shell::shellAvailable()) throw new Exceptions\ShellUnavailableException;
        
        return !is_null(`command -v $name`);
    }
    
    static function __callStatic(string $name, array $arguments): object
    {
        if(!Shell::commandAvailable($name)) throw new Exceptions\UnknownCommandException($name);
        
        $command = implode(" ", array_merge([$name], $arguments));
        
        return new class($command)
        {
            private $command;
            
            function __construct(string $cmd)
            {
                $this->command = $cmd;
            }
            
            function execute(): string
            {
                return shell_exec($this->command);
            }
            
            function start(): string
            {
                shell_exec("nohup " . $this->command . " > /dev/null 2>/dev/null &");
                
                return $this->command;
            }
        };
    }
}
