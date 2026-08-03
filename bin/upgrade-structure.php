#!/usr/bin/env php
<?php

declare(strict_types=1);

in_array('--help', $argv ?? []) && exit(showHelp());

$extract      = in_array('--extract', $argv ?? [], true);
$dryRun       = in_array('--dry-run', $argv ?? [], true);
$force        = in_array('--force', $argv ?? [], true);
$targetOvk    = '/opt/openvk';
$chandlerRoot = null;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--target-ovk=')) {
        $targetOvk = substr($arg, strlen('--target-ovk='));
    }
    if (str_starts_with($arg, '--chandler-root=')) {
        $chandlerRoot = substr($arg, strlen('--chandler-root='));
    }
}

function showHelp(): int
{
    echo <<<HELP
        \033[1mOpenVK — Structural Migration\033[0m

        Migrates an OpenVK instance from the old Chandler extension structure
        (extensions/available/openvk) to the new standalone architecture.

        \033[1mUsage:\033[0m
          php bin/upgrade-structure.php [options]

        \033[1mOptions:\033[0m
          --extract               Move OpenVK out of extensions/ into a separate directory
          --target-ovk=PATH       Target directory for --extract (default: /opt/openvk)
          --chandler-root=PATH    Path to Chandler root (auto-detected by default)
          --dry-run               Show what would be done without making changes
          --force                 Skip permission and disk space checks
          --help                  Show this help

        \033[1mExamples:\033[0m
          php bin/upgrade-structure.php --dry-run --extract
          php bin/upgrade-structure.php --extract --target-ovk=/var/www/openvk
          php bin/upgrade-structure.php

        HELP;
    return 0;
}

function info(string $msg): void
{
    echo "  \033[36m→\033[0m $msg\n";
}
function ok(string $msg): void
{
    echo "  \033[32m✓\033[0m $msg\n";
}
function warn(string $msg): void
{
    echo "  \033[33m⚠\033[0m $msg\n";
}
function error(string $msg): void
{
    echo "  \033[31m✗\033[0m $msg\n";
}

function run(string $cmd, ?string $cwd = null): ?string
{
    $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptors, $pipes, $cwd);
    if (!$proc) {
        return null;
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return proc_close($proc) === 0 ? ($stdout ?: '') : null;
}

function humanBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes >= 1024 && $i < 3; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 1) . $units[$i];
}

// ─── Path detection ──────────────────────────────────────────────────

$ovkRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
if (!$ovkRoot || $ovkRoot === '.') {
    fwrite(STDERR, "Cannot determine OpenVK root.\n");
    exit(1);
}

if (!$chandlerRoot) {
    $candidates = [
        dirname($ovkRoot, 3) . '/chandler/Bootstrap.php',
        $ovkRoot . '/vendor/openvk/chandler/chandler/Bootstrap.php',
        dirname($ovkRoot) . '/chandler/chandler/Bootstrap.php',
        '/opt/chandler/chandler/Bootstrap.php',
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $chandlerRoot = dirname(dirname($candidate));
            break;
        }
        $candidateReal = realpath($candidate);
        if ($candidateReal && file_exists($candidateReal)) {
            $chandlerRoot = dirname(dirname($candidateReal));
            break;
        }
    }
}

if (!$chandlerRoot) {
    fwrite(STDERR, "Cannot detect Chandler root. Pass --chandler-root=/path/to/chandler\n");
    exit(1);
}

// Resolve to the real location for operations (backup, etc.)
$chandlerRootReal = realpath($chandlerRoot) ?: $chandlerRoot;
$isInsideExtensions = str_contains($ovkRoot, $chandlerRoot . '/extensions/');

echo "\033[1mOpenVK — Structural Migration\033[0m\n";
echo "  OpenVK root:     $ovkRoot\n";
echo "  Chandler root:   $chandlerRootReal\n";
echo "  Structure:       " . ($isInsideExtensions ? "old (inside extensions/)" : "standalone") . "\n";
if ($extract) {
    echo "  Target:          $targetOvk\n";
}
if ($dryRun) {
    echo "  \033[33mDRY RUN — no changes will be made\033[0m\n";
}
echo "\n";

// ─── Pre-flight ─────────────────────────────────────────────────────

info("Running pre-flight checks...");

$checkDirs = [$ovkRoot, $chandlerRootReal];
if ($extract) {
    $checkDirs[] = dirname($targetOvk);
}
$permErrors = [];
foreach ($checkDirs as $dir) {
    if (!is_dir($dir) && !@mkdir($dir, 0o755, true)) {
        $permErrors[] = $dir;
    } elseif (!is_writable($dir)) {
        $permErrors[] = $dir;
    }
}
if ($permErrors) {
    error("Insufficient write permissions in:");
    foreach ($permErrors as $d) {
        error("  $d");
    }
    $owner = posix_getpwuid(fileowner($permErrors[0]))['name'] ?? 'unknown';
    $sudo  = $owner === 'root' || $owner === 'unknown' ? "sudo php bin/upgrade-structure.php" : "sudo -u $owner php bin/upgrade-structure.php";
    warn("Files are owned by '$owner'. Try: $sudo");
    if (!$force) {
        exit(1);
    }
    warn("--force: continuing anyway...");
}

if (!$dryRun && !$force) {
    $chandlerSize = (int) shell_exec("du -sb " . escapeshellarg($chandlerRoot) . " 2>/dev/null | cut -f1");
    $freeSpace    = disk_free_space($chandlerRoot) ?: 0;
    if ($chandlerSize > $freeSpace * 0.8) {
        warn("Backup would use " . humanBytes($chandlerSize) . " but only " . humanBytes($freeSpace) . " free.");
        warn("Use --force to skip this check.");
        echo "  Continue anyway? [y/N] ";
        $ans = trim(fgets(STDIN) ?: '');
        if (strtolower($ans) !== 'y') {
            error("Aborted.");
            exit(1);
        }
    }
}

// ─── Backup ──────────────────────────────────────────────────────────

$timestamp = date('Ymd-His');
$backupDir = is_writable(dirname($chandlerRootReal)) ? dirname($chandlerRootReal) : '/tmp';
$backupPath = $backupDir . '/chandler-backup-' . $timestamp;

info("Creating backup of Chandler to $backupPath ...");

$rsyncExcludes = [];
if ($isInsideExtensions) {
    $ovkStorage = $ovkRoot . '/storage';
    if (is_dir($ovkStorage)) {
        $rel = str_replace($chandlerRoot . '/', '', $ovkStorage);
        $rsyncExcludes[] = '--exclude=' . escapeshellarg($rel);
    }
}
$rsyncExcludes[] = '--exclude=vendor';
$rsyncExcludes[] = '--exclude=tmp';
$rsyncExcludes[] = '--exclude=logs';

if (!$dryRun) {
    $cmd = 'rsync -a ' . implode(' ', $rsyncExcludes) . ' ' . escapeshellarg($chandlerRootReal . '/') . ' ' . escapeshellarg($backupPath);
    $result = run($cmd);
    if ($result === null) {
        warn("Backup may have failed. Check manually.");
        exit(1);
    } else {
        ok($isInsideExtensions ? "Backup (without OpenVK storage) created at $backupPath." : "Backup created at $backupPath.");
    }
} else {
    ok("[DRY-RUN] Would create backup at $backupPath.");
}

// ─── Load autoloader for YAML ────────────────────────────────────────

$autoloadLoaded = false;
$autoloadPaths = [
    $ovkRoot . '/vendor/autoload.php',
    $chandlerRootReal . '/vendor/autoload.php',
    $backupPath . '/vendor/autoload.php',
];
foreach ($autoloadPaths as $p) {
    if (file_exists($p) && !$autoloadLoaded) {
        require $p;
        $autoloadLoaded = class_exists(\Symfony\Component\Yaml\Yaml::class);
    }
}

// ─── Config merge ────────────────────────────────────────────────────

$chandlerConfPath = $chandlerRootReal . '/chandler.yml';
$ovkConfPath      = $ovkRoot . '/openvk.yml';

if (file_exists($chandlerConfPath) && file_exists($ovkConfPath)) {
    info("Merging chandler.yml into openvk.yml...");

    $parseYaml = function (string $path) use ($autoloadLoaded): ?array {
        if (function_exists('yaml_parse_file')) {
            $result = yaml_parse_file($path);
            return $result !== false ? $result : null;
        }
        if ($autoloadLoaded) {
            return \Symfony\Component\Yaml\Yaml::parseFile($path);
        }
        return null;
    };

    $dumpYaml = function (array $data) use ($autoloadLoaded): ?string {
        if (function_exists('yaml_emit')) {
            $result = yaml_emit($data, YAML_UTF8_ENCODING);
            return $result !== false ? $result : null;
        }
        if ($autoloadLoaded) {
            return \Symfony\Component\Yaml\Yaml::dump($data, 8, 2);
        }
        return null;
    };

    $chandlerConf = $parseYaml($chandlerConfPath);
    $ovkConf      = $parseYaml($ovkConfPath);

    if ($chandlerConf !== null && $ovkConf !== null) {
        if (isset($chandlerConf['chandler'])) {
            $chandlerSection = $chandlerConf['chandler'];
            unset($chandlerSection['extensions']);
            $ovkConf['chandler'] = $chandlerSection;
        }

        $yaml = $dumpYaml($ovkConf);
        if ($yaml !== null) {
            if (!$dryRun) {
                file_put_contents($ovkConfPath, $yaml);
            }
            ok("Configuration merged into $ovkConfPath.");
        } else {
            error("Failed to dump YAML.");
        }
    } else {
        warn("Cannot parse YAML. Install ext-yaml or run composer install first.");
    }
} elseif (file_exists($chandlerConfPath)) {
    warn("$ovkConfPath not found; cannot merge.");
} else {
    ok("No chandler.yml to merge — skipping.");
}

// ─── Extract ─────────────────────────────────────────────────────────

if ($extract && $isInsideExtensions) {
    info("Extracting OpenVK to $targetOvk...");

    if (file_exists($targetOvk) || is_link($targetOvk)) {
        $targetReal = realpath($targetOvk);
        if ($targetReal && $targetReal === $ovkRoot) {
            if (!$dryRun) {
                if (is_link($targetOvk)) {
                    unlink($targetOvk);
                }
            }
            ok("Removed symlink $targetOvk → current source.");
        } else {
            error("Target $targetOvk already exists. Remove it first or use --target-ovk=...");
            exit(1);
        }
    }

    if (!is_dir(dirname($targetOvk))) {
        if (!$dryRun) {
            if (!@mkdir(dirname($targetOvk), 0o755, true)) {
                error("Cannot create target directory.");
                exit(1);
            }
        }
        ok("Created target parent directory.");
    }

    if (!$dryRun) {
        if (!rename($ovkRoot, $targetOvk)) {
            error("Failed to move OpenVK to $targetOvk.");
            exit(1);
        }
        $ovkRoot = realpath($targetOvk);
    }
    ok("OpenVK moved to $targetOvk.");
} elseif ($extract && !$isInsideExtensions) {
    warn("OpenVK is already standalone. --extract has no effect.");
}

// ─── Cleanup old structure ───────────────────────────────────────────

if ($isInsideExtensions) {
    info("Cleaning up old extension structure...");

    $itemsToRemove = [
        $chandlerRootReal . '/extensions/enabled/openvk',
    ];
    if ($extract) {
        $itemsToRemove[] = $chandlerRootReal . '/extensions/available/openvk';
    }
    foreach ($itemsToRemove as $item) {
        if (file_exists($item) || is_link($item)) {
            if ($dryRun) {
                ok("[DRY-RUN] Would remove $item.");
                continue;
            }
            if (is_link($item) || is_file($item)) {
                unlink($item);
            } else {
                $it = new RecursiveDirectoryIterator($item, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $f) {
                    $path = $f->getRealPath() ?: $f->getPathname();
                    $f->isDir() && !$f->isLink() ? rmdir($path) : @unlink($path);
                }
                rmdir($item);
            }
            ok("Removed $item.");
        }
    }

    // Clean up empty directories
    foreach (['extensions/enabled', 'extensions/available', 'extensions'] as $sub) {
        $path = $chandlerRootReal . '/' . $sub;
        if (is_dir($path)) {
            $it = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
            if (!$it->valid()) {
                if (!$dryRun) {
                    rmdir($path);
                }
                ok("Removed empty $path.");
            }
        }
    }

    if (file_exists($chandlerConfPath)) {
        if (!$dryRun) {
            unlink($chandlerConfPath);
        }
        ok("Removed $chandlerConfPath.");
    }
}

// ─── Composer install ────────────────────────────────────────────────

if (!$dryRun && file_exists($ovkRoot . '/composer.json')) {
    info("Running composer install...");
    $result = run('composer install --no-interaction 2>&1', $ovkRoot);
    if ($result === null) {
        warn("composer install may have failed. Run: composer install --no-interaction");
    } else {
        ok("composer install completed.");
    }
}

// ─── Validation ──────────────────────────────────────────────────────

info("Validating installation...");
$errors = 0;

if (file_exists($ovkRoot . '/bootstrap.php')) {
    $output = run('php -r "require ' . var_export($ovkRoot . '/bootstrap.php', true) . ';" 2>&1', $ovkRoot);
    if ($output === null) {
        warn("bootstrap.php check failed.");
        $errors++;
    } else {
        ok("bootstrap.php loads successfully.");
    }
} else {
    warn("bootstrap.php not found.");
    $errors++;
}

if (file_exists($ovkRoot . '/openvkctl')) {
    $output = run('php ' . escapeshellarg($ovkRoot . '/openvkctl') . ' 2>&1', $ovkRoot);
    if ($output === null || !str_contains($output, 'Console Tool')) {
        warn("openvkctl check failed.");
        $errors++;
    } else {
        ok("openvkctl works.");
    }
} else {
    warn("openvkctl not found.");
    $errors++;
}

if (file_exists($chandlerRootReal . '/vendor/autoload.php')) {
    $output = run('php -r "require ' . var_export($chandlerRootReal . '/vendor/autoload.php', true) . '; echo \'OK\';" 2>&1');
    if (trim($output ?? '') === 'OK') {
        ok("Chandler autoloader OK.");
    } else {
        warn("Chandler autoloader check failed.");
        $errors++;
    }
}

// ─── Summary ─────────────────────────────────────────────────────────

echo "\n";
if ($errors === 0) {
    ok("Migration complete.");
} else {
    warn("Migration finished with $errors warning(s).");
}

echo "\n\033[1mNext steps:\033[0m\n";
if ($extract && $isInsideExtensions) {
    echo "  1. Change webserver DocumentRoot from $chandlerRootReal/htdocs to $ovkRoot/htdocs\n";
    echo "  2. Update cron/systemd paths: $ovkRoot/openvkctl\n";
}
echo "  3. Review configuration: $ovkRoot/openvk.yml\n";
echo "  4. Remove backup when ready: rm -rf $backupPath\n";
