#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\EventSourcing\Examples\MediaQueryContext;
use BEAR\EventSourcing\Examples\MediaQueryResultContext;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\ResourceResponseContext;
use BEAR\EventSourcing\Resource\Stree\ResourceNodeFormatter;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\Stree\FormatterRegistry;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeRenderer;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/MediaQueryContext.php';
require __DIR__ . '/MediaQueryResultContext.php';

// A DevLogModule-style observation log: each response keeps only a body_ref
// pointing at the externalized body file, so the tree carries a pointer the AI
// can follow to the full detail. The inventory resource embeds a media query
// (a non-resource operation) whose rows sit behind the same kind of pointer.
$logger = new SemanticLogger();
$order = $logger->open(new ResourceRequestContext(
    uri: 'app://self/orders',
    method: 'POST',
    params: ['order_id' => 'O-1000'],
    timestamp: '2026-06-10T12:36:00.000000+00:00',
));
$inventory = $logger->open(new ResourceRequestContext(
    uri: 'app://self/inventory/SKU-1',
    method: 'PUT',
    params: ['sku' => 'SKU-1', 'quantity' => 1],
    timestamp: '2026-06-10T12:36:01.000000+00:00',
));
$query = $logger->open(new MediaQueryContext('inventory_reserve', 'SKU-1'));
$logger->close(new MediaQueryResultContext('file://var/es/rows/000001.json'), $query);
$logger->close(new ResourceResponseContext(200, 'file://var/es/bodies/000001.json'), $inventory);
$logger->close(new ResourceResponseContext(201, 'file://var/es/bodies/000002.json'), $order);

$formatters = new FormatterRegistry();
$formatters->register('resource_request', new ResourceNodeFormatter());

$config = new RenderConfig(
    showFullTree: false,
    timeThreshold: 0.0,
    maxLines: 0,
    formatters: $formatters,
);

echo (new TreeRenderer())->render($logger->flush()->toArray(), $config), "\n";
