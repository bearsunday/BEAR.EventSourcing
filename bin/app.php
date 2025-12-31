#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\Package\Bootstrap;
use BearEccube\Module\AppModule;

require dirname(__DIR__) . '/vendor/autoload.php';

$context = $argv[1] ?? 'app';

exit((new Bootstrap())->getApp('BearEccube', $context)->run());
