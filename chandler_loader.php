#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace openvk;

$_SERVER["HTTP_ACCEPT_LANGUAGE"] = false;
$bootstrap = require(__DIR__ . "/../../../chandler/Bootstrap.php");
$bootstrap->ignite(true);
