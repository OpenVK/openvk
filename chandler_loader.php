#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace openvk;

$_SERVER["HTTP_ACCEPT_LANGUAGE"] = false;
require __DIR__ . "/bootstrap.php";
bootstrap_openvk(true);
