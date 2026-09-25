#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace openvk;

use Chandler\Cron\Scheduler;
use Chandler\Cron\CronRunner;
use openvk\CLI\CleanupPendingUploadsCommand;
use openvk\CLI\FetchToncoinTransactions;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

require __DIR__ . "/chandler_loader.php";

$scheduler = Scheduler::i();

$scheduler->command(CleanupPendingUploadsCommand::class, "executeCleanup", "cleanups.pending_uploads")
    ->description("Cleanup pending photo uploads older than 24 hours")
    ->hourly()
    ->withoutOverlapping();

$scheduler->call(function (): void {
    $command = new FetchToncoinTransactions();
    $output  = new ConsoleOutput(OutputInterface::VERBOSITY_QUIET);
    $command->run(new ArrayInput([]), $output);
}, "ton.fetch_transactions")
    ->description("Fetch TON transactions and top up user balances")
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn(): bool => (bool) (OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["enabled"] ?? false));

exit(CronRunner::run($argv, $scheduler));
