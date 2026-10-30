<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

const CANONICAL_CONFIG = "openvk-example.yml";
const CONFIGS = [
    "tests/openvk.test.yml",
    "install/automated/common/openvk.template.yml",
    "install/automated/docker/openvk.example.yml",
    "install/automated/kubernetes/manifests/001-configmap.yaml",
];

$root = dirname(__DIR__);
if (!function_exists("yaml_parse_file") && is_file("$root/vendor/autoload.php")) {
    require_once "$root/vendor/autoload.php";
}

function parseYaml(string $yaml): array
{
    if (function_exists("yaml_parse")) {
        $data = yaml_parse($yaml);
    } elseif (class_exists(Yaml::class)) {
        $data = Yaml::parse($yaml);
    } else {
        throw new RuntimeException("Install ext-yaml or run composer install");
    }

    if (!is_array($data)) {
        throw new RuntimeException("Failed to parse YAML");
    }

    return $data;
}

function loadConfig(string $path): array
{
    if (function_exists("yaml_parse_file")) {
        $data = yaml_parse_file($path);
    } elseif (class_exists(Yaml::class)) {
        $data = Yaml::parseFile($path);
    } else {
        throw new RuntimeException("Install ext-yaml or run composer install");
    }

    if (!is_array($data)) {
        throw new RuntimeException("Failed to parse $path");
    }

    $embedded = $data["data"]["openvk.yml"] ?? null;

    return is_string($embedded) ? parseYaml($embedded) : $data;
}

/**
 * Flattens map keys into dot paths. Lists are treated as leaves: their contents
 * are environment-specific, only the key itself has to exist.
 */
function flattenKeys(array $data, string $prefix = ""): array
{
    $keys = [];
    foreach ($data as $key => $value) {
        $path = $prefix === "" ? (string) $key : "$prefix.$key";
        $keys[$path] = true;
        if (is_array($value) && $value !== [] && array_keys($value) !== range(0, count($value) - 1)) {
            $keys += flattenKeys($value, $path);
        }
    }

    return $keys;
}

$canonical = flattenKeys(loadConfig("$root/" . CANONICAL_CONFIG));
$failed = false;

foreach (CONFIGS as $config) {
    $keys = flattenKeys(loadConfig("$root/$config"));
    $missing = array_keys(array_diff_key($canonical, $keys));
    $extra = array_keys(array_diff_key($keys, $canonical));

    if ($missing === [] && $extra === []) {
        echo "OK $config\n";
        continue;
    }

    $failed = true;
    echo "FAIL $config\n";
    foreach ($missing as $key) {
        echo "  missing: $key\n";
    }
    foreach ($extra as $key) {
        echo "  extra:   $key\n";
    }
}

exit($failed ? 1 : 0);
