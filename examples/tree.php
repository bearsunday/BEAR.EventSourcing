#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\EventSourcing\Resource\Stree\ResourceNodeFormatter;
use Koriym\SemanticLogger\Stree\FormatterRegistry;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeRenderer;

use function BEAR\EventSourcing\Examples\exampleSemanticLog;

require __DIR__ . '/bootstrap.php';

$formatters = new FormatterRegistry();
$formatters->register('resource_request', new ResourceNodeFormatter());

$config = new RenderConfig(
    showFullTree: false,
    timeThreshold: 0.0,
    maxLines: 0,
    formatters: $formatters,
);

echo (new TreeRenderer())->render(exampleSemanticLog()->toArray(), $config), "\n";
