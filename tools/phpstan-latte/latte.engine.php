<?php

declare(strict_types=1);

namespace openvk\PHPStanLatte;

use Chandler\MVC\SimplePresenter;
use Latte\Essential\TranslatorExtension;
use function class_exists;
use function define;
use function defined;
use function function_exists;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;

/*
 * Latte engine bootstrap for PHPStan (latte.engineBootstrap).
 *
 * Mirrors OpenVKPresenter::getTemplatingEngine(): the chandler engine plus
 * the translator extension. SimplePresenter needs CHANDLER_ROOT for the
 * Latte cache directory; static analysis only compiles templates, so a
 * scratch directory is sufficient.
 */
if (!defined('CHANDLER_ROOT')) {
    define('CHANDLER_ROOT', sys_get_temp_dir() . '/openvk-phpstan-latte');
}

$tempDirectory = CHANDLER_ROOT . '/tmp/cache/templates';
if (!is_dir($tempDirectory)) {
    mkdir($tempDirectory, recursive: true);
}

if (!class_exists(AnalysisPresenter::class, false)) {
    final class AnalysisPresenter extends SimplePresenter {}
}

$latte = (new AnalysisPresenter())->getTemplatingEngine();
if (function_exists('tr')) {
    $latte->addExtension(new TranslatorExtension(tr(...)));
}

return $latte;
