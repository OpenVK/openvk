#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace openvk;

use Chandler\Cron\CronRunner;

require __DIR__ . "/chandler_loader.php";

exit(CronRunner::run($argv));
