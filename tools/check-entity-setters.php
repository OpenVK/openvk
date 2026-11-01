<?php

declare(strict_types=1);

/*
 * Verifies that every magic setter declared with @method on a DBEntity model
 * points to an existing database column.
 *
 * Run against a database that was prepared with `./openvkctl upgrade`.
 */

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__);

require $root . "/vendor/autoload.php";

$configPath = $root . "/openvk.yml";
$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if (str_starts_with($arg, "--config=")) {
        $configPath = substr($arg, strlen("--config="));
    } elseif ($arg === "--help" || $arg === "-h") {
        echo "Usage: php tools/check-entity-setters.php [--config=openvk.yml]\n";
        exit(0);
    } else {
        fwrite(STDERR, "Unknown argument: $arg\n");
        exit(2);
    }
}

if (!is_file($configPath)) {
    fwrite(STDERR, "Config file not found: $configPath\n");
    exit(2);
}

$config = Yaml::parseFile($configPath);
$database = $config["chandler"]["database"] ?? null;
if (!is_array($database) || empty($database["dsn"])) {
    fwrite(STDERR, "chandler.database is missing or incomplete in $configPath\n");
    exit(2);
}

try {
    $pdo = new PDO(
        $database["dsn"],
        $database["user"] ?? "",
        $database["password"] ?? "",
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Could not connect to the database: {$e->getMessage()}\n");
    exit(2);
}

$columns = [];
foreach ($pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()") as [$table, $column]) {
    $columns[$table][$column] = true;
}

/**
 * @return array<string, array{table: ?string, parent: ?string, methods: array<int, array{name: string, line: int}>}>
 */
function collectClasses(string $directory): array
{
    $classes = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->getExtension() !== "php") {
            continue;
        }

        $source = file_get_contents($file->getPathname());
        if ($source === false) {
            continue;
        }

        preg_match_all(
            '/(?P<doc>\/\*\*.*?\*\/\s*)?(?:(?:abstract|final)\s+)*class\s+(?P<name>\w+)(?:\s+extends\s+(?P<parent>[\w\\\\]+))?\s*\{/s',
            $source,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $name = $match["name"][0];
            $offset = $match["name"][1];
            $doc = $match["doc"][0] ?? "";
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;

            $table = null;
            if (preg_match('/\$tableName\s*=\s*["\']([^"\']+)["\']/', substr($source, $offset), $tableMatch)) {
                $table = $tableMatch[1];
            }

            $methods = [];
            if (preg_match_all('/@method\s+(?:static\s+)?(?:[^\s]+\s+)?(set\w+)\s*\(/', $doc, $methodMatches)) {
                foreach ($methodMatches[1] as $method) {
                    $methods[] = ["name" => $method, "line" => $line];
                }
            }

            $parent = isset($match["parent"]) && $match["parent"][0] !== "" ? $match["parent"][0] : null;
            if ($parent !== null && str_contains($parent, "\\")) {
                $parent = substr($parent, strrpos($parent, "\\") + 1);
            }

            $classes[$name] = [
                "table"   => $table,
                "parent"  => $parent,
                "methods" => $methods,
            ];
        }
    }

    return $classes;
}

/**
 * @param array<string, array{table: ?string, parent: ?string, methods: array<int, array{name: string, line: int}>}> $classes
 */
function resolveTable(string $class, array $classes): ?string
{
    $seen = [];
    while (isset($classes[$class]) && !isset($seen[$class])) {
        if ($classes[$class]["table"] !== null) {
            return $classes[$class]["table"];
        }

        $seen[$class] = true;
        $class = $classes[$class]["parent"] ?? "";
    }

    return null;
}

/**
 * @param array<string, array{table: ?string, parent: ?string, methods: array<int, array{name: string, line: int}>}> $classes
 * @return array<int, string>
 */
function descendants(string $class, array $classes): array
{
    $result = [];
    foreach ($classes as $name => $data) {
        if ($data["parent"] === $class) {
            $result[] = $name;
            $result = array_merge($result, descendants($name, $classes));
        }
    }

    return $result;
}

$classes = collectClasses($root . "/Web/Models/Entities");
$errors = [];

foreach ($classes as $class => $data) {
    if ($data["methods"] === []) {
        continue;
    }

    $tables = [];
    $table = resolveTable($class, $classes);
    if ($table !== null) {
        $tables[] = $table;
    } else {
        foreach (descendants($class, $classes) as $child) {
            $childTable = resolveTable($child, $classes);
            if ($childTable !== null) {
                $tables[] = $childTable;
            }
        }
    }

    if ($tables === []) {
        $errors[] = "$class has @method setters but no \$tableName";
        continue;
    }

    foreach ($data["methods"] as $method) {
        $column = mb_strtolower(substr($method["name"], 3));

        foreach (array_unique($tables) as $tableName) {
            if (!isset($columns[$tableName][$column])) {
                $errors[] = sprintf(
                    "%s::%s() (line %d) points to missing column %s.%s",
                    $class,
                    $method["name"],
                    $method["line"],
                    $tableName,
                    $column
                );
            }
        }
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "::error::$error\n");
    }

    exit(1);
}

echo "OK: all entity setters point to existing database columns\n";
