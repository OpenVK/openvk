<?php

declare(strict_types=1);

namespace openvk\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPendingUploadsCommand extends Command
{
    protected static $defaultName = "cleanup-pending-uploads";

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Cleanup pending photo uploads older than specified time")
             ->addOption(
                 "max-age",
                 "a",
                 InputOption::VALUE_OPTIONAL,
                 "Maximum age in hours (default: 24)",
                 24
             )
             ->addOption(
                 "dry-run",
                 "d",
                 InputOption::VALUE_NONE,
                 "Show what would be deleted without actually deleting"
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $maxAge = (int) $input->getOption("max-age");
        $dryRun = $input->getOption("dry-run");

        $photoFolder = __DIR__ . "/../tmp/api-storage/photos";

        if (!is_dir($photoFolder)) {
            $output->writeln("<error>Photo upload directory not found: {$photoFolder}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning for pending uploads older than {$maxAge} hours...</info>");

        $cutoffTime = time() - ($maxAge * 3600);
        $deletedCount = 0;
        $totalSize = 0;

        $files = glob($photoFolder . "/*_*.oct");

        foreach ($files as $file) {
            $fileTime = filemtime($file);

            if ($fileTime < $cutoffTime) {
                $fileSize = filesize($file);
                $totalSize += $fileSize;

                if ($dryRun) {
                    $age = round((time() - $fileTime) / 3600, 1);
                    $output->writeln("<comment>Would delete: " . basename($file) . " (age: {$age}h, size: " . $this->formatBytes($fileSize) . ")</comment>");
                } else {
                    if (unlink($file)) {
                        $deletedCount++;
                        $output->writeln("<info>Deleted: " . basename($file) . "</info>");
                    } else {
                        $output->writeln("<error>Failed to delete: " . basename($file) . "</error>");
                    }
                }
            }
        }

        if ($dryRun) {
            $output->writeln("<info>Dry run completed. Would delete {$deletedCount} files (" . $this->formatBytes($totalSize) . ")</info>");
        } else {
            $output->writeln("<info>Cleanup completed. Deleted {$deletedCount} files (" . $this->formatBytes($totalSize) . ")</info>");
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
