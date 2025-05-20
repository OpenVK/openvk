<?php

declare(strict_types=1);

/*
 * Analysis-time stand-ins for constants that the application defines at
 * runtime. PHPStan does not boot the application, so declare them here for
 * static analysis only.
 */
if (!defined('CONNECTING_IP')) {
    define('CONNECTING_IP', '127.0.0.1');
}

if (!defined('VKAPI_DECL_VER')) {
    define('VKAPI_DECL_VER', '5.9999');
}

if (!defined('VKAPI_OVK_APP')) {
    define('VKAPI_OVK_APP', '5.9999');
}

if (!defined('VKAPI_DECL_VER_MAJOR')) {
    define('VKAPI_DECL_VER_MAJOR', 5);
}

if (!defined('VKAPI_DECL_VER_MINOR')) {
    define('VKAPI_DECL_VER_MINOR', 0);
}
