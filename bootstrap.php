<?php

declare(strict_types=1);

use Chandler\Extensions\ExtensionManager;
use Chandler\MVC\Routing\Router;

function bootstrap_openvk(bool $headless = false): void
{
    require __DIR__ . "/vendor/autoload.php";

    define("CHANDLER_ROOT", __DIR__, false);
    define("OPENVK_ROOT", __DIR__, false);

    chandler_init_yaml_cache();

    $config = chandler_parse_yaml(__DIR__ . "/openvk.yml");
    define("CHANDLER_ROOT_CONF", $config["chandler"], false);
    define("OPENVK_ROOT_CONF", $config, false);

    ExtensionManager::registerBuiltin("openvk", __DIR__, [
        "name" => "OpenVK",
        "init" => "ovk-init.php",
    ]);

    require_once __DIR__ . "/vendor/openvk/chandler/chandler/Bootstrap.php";
    $bootstrap = new Bootstrap(__DIR__, false, __DIR__ . "/openvk.yml");
    $bootstrap->ignite($headless);
}
