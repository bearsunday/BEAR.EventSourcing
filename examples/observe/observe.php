#!/usr/bin/env php
<?php

declare(strict_types=1);

// Comprehensive walk-through of BEAR.EventSourcing observation.
//
// Runs a live BEAR.Resource application and demonstrates, end to end:
//   1. DevLogModule recording a request tree (nested writes, a read, a failure)
//   2. FileBodyStore externalizing response bodies behind body_ref
//   3. Rendering the observation log as a tree
//   4. Extracting events (state-changing writes only; failures dropped)
//   5. Deterministic event identity (re-extraction yields the same ids)
//   6. Filtering events with standard iterators
//   7. InMemoryEventStore with idempotent re-append
//   8. MediaQueryEventStore (SQLite) round-trip and idempotency
//   9. Replaying the stored stream in order
//  10. Validating every observation context against its published JSON Schema

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\Stree\ResourceNodeFormatter;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\Store\InMemoryEventStore;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Composer\InstalledVersions;
use FakeApp\EventStoreModule;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Koriym\SemanticLogger\SemanticLogValidator;
use Koriym\SemanticLogger\Stree\FormatterRegistry;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeRenderer;
use Ray\Di\Injector;

$loader = require dirname(__DIR__, 2) . '/vendor/autoload.php';
$loader->addPsr4('FakeApp\\', __DIR__);

$projectDir = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/bear-es-observe-' . bin2hex(random_bytes(4));
mkdir($tmp . '/bodies', 0777, true);

// ---------------------------------------------------------------------------
// 1. Live observation. DevLogModule decorates the real BEAR.Resource invoker;
//    it records a request tree and stores rendered bodies behind body_ref.
// ---------------------------------------------------------------------------
$injector = new Injector(new DevLogModule(
    bodyDir: $tmp . '/bodies',
    module: new ResourceModule('FakeApp'),
));
$resource = $injector->getInstance(ResourceInterface::class);

$resource->post->uri('app://self/orders')(['order_id' => 'O-1000']); // nested inventory PUT
$resource->uri('app://self/orders')(['order_id' => 'O-1000']);        // GET read (WITH_READS)
$resource->delete->uri('app://self/orders')(['order_id' => 'O-1000']); // failed write, 409

$log = $injector->getInstance(SemanticLoggerInterface::class)->flush();

echo "[1] Live requests executed (recorded by DevLogModule)\n";

// ---------------------------------------------------------------------------
// 2. Render the observation log as a tree: intent inline, detail behind *_ref.
// ---------------------------------------------------------------------------
$formatters = new FormatterRegistry();
$formatters->register('resource_request', new ResourceNodeFormatter());
$config = new RenderConfig(showFullTree: false, timeThreshold: 0.0, maxLines: 0, formatters: $formatters);
echo "\n[2] Observation log as a tree\n";
echo (new TreeRenderer($config))->render($log);

// ---------------------------------------------------------------------------
// 3. Bodies externalized by FileBodyStore, pointed to by body_ref.
// ---------------------------------------------------------------------------
$bodyFiles = glob($tmp . '/bodies/*.json');
echo "\n[3] Bodies behind body_ref (" . count($bodyFiles) . " file(s))\n";
foreach ($bodyFiles as $file) {
    printf("  %s  %s\n", basename((string) $file), str_replace("\n", ' ', trim((string) file_get_contents($file))));
}

// ---------------------------------------------------------------------------
// 4. Extraction. Only state-changing writes become events; the 409 DELETE is
//    observed but dropped. GET is observation data, not a state change.
// ---------------------------------------------------------------------------
$events = (new SemanticLogExtractor())->extract($log);
echo "\n[4] Extracted events (writes only; failed 409 and GET are dropped)\n";
foreach ($events as $event) {
    printf("  %s %s  params=%s\n", $event->method, $event->uri, json_encode($event->params, JSON_UNESCAPED_SLASHES));
}

// ---------------------------------------------------------------------------
// 5. Deterministic identity: the same log extracts to the same ids.
// ---------------------------------------------------------------------------
$ids = [];
foreach ($events as $event) {
    $ids[] = $event->id;
}

$again = (new SemanticLogExtractor())->extract($log);
$againIds = [];
foreach ($again as $event) {
    $againIds[] = $event->id;
}

echo "\n[5] Deterministic event identity\n";
printf("  re-extraction produced identical ids: %s\n", $ids === $againIds ? 'yes' : 'NO');

// ---------------------------------------------------------------------------
// 6. Filtering with PHP's standard iterators — no query methods on Events.
// ---------------------------------------------------------------------------
$inventoryWrites = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => $event->method === 'PUT' && str_contains($event->uri, 'inventory'),
);
echo "\n[6] Filtered with CallbackFilterIterator\n";
foreach ($inventoryWrites as $event) {
    printf("  %s %s\n", $event->method, $event->uri);
}

// ---------------------------------------------------------------------------
// 7. InMemoryEventStore: appending is idempotent per event id.
// ---------------------------------------------------------------------------
$memory = new InMemoryEventStore();
$memory->appendAll($events);
$memory->appendAll($events); // a retried batch must not duplicate facts

echo "\n[7] InMemoryEventStore\n";
printf("  events stored after re-append: %d (no duplicates)\n", count($memory->all()));

// ---------------------------------------------------------------------------
// 8. MediaQueryEventStore (SQLite), the application-owned SQL destination.
//    aura/sql < 6 cannot load on PHP >= 8.4, so this section is guarded the
//    same way the test suite guards it.
// ---------------------------------------------------------------------------
$auraSqlVersion = (string) InstalledVersions::getVersion('aura/sql');
$sqlSupported = ! (PHP_VERSION_ID >= 80400 && version_compare($auraSqlVersion, '6.0.0', '<'));
$dbFile = null;

echo "\n[8] MediaQueryEventStore (SQLite)\n";
if (! $sqlSupported) {
    echo "  skipped: aura/sql $auraSqlVersion cannot load on PHP >= 8.4 (run this demo under PHP < 8.4)\n";
} else {
    $dbFile = $tmp . '/events.sqlite';
    $schema = file_get_contents($projectDir . '/sql/event_store/schema.sql');
    (new PDO('sqlite:' . $dbFile))->exec($schema);

    $sqlInjector = new Injector(new EventStoreModule($dbFile, $projectDir));
    $sqlStore = $sqlInjector->getInstance(EventStoreInterface::class);
    $sqlStore->appendAll($events);
    $sqlStore->appendAll($events); // idempotent per event_id (UNIQUE)

    $stored = iterator_to_array($sqlStore->all(), false);
    printf("  stored rows after re-append: %d\n", count($stored));
    foreach ($stored as $event) {
        printf("  %s %s  recorded_at %s\n", $event->method, $event->uri, $event->timestamp->format('P'));
    }
}

// ---------------------------------------------------------------------------
// 9. Replay: iterate the stored stream in time order.
// ---------------------------------------------------------------------------
echo "\n[9] Replay the stored stream\n";
$source = $sqlSupported ? iterator_to_array($sqlStore->all(), false) : iterator_to_array($memory->all(), false);
foreach ($source as $event) {
    printf("  replay %s %s  id=%.12s...\n", $event->method, $event->uri, $event->id);
}

// ---------------------------------------------------------------------------
// 10. Schema validation: each context names its schemaUrl; validate offline
//     against docs/schemas. A contract break makes this demo exit non-zero.
// ---------------------------------------------------------------------------
echo "\n[10] Schema validation against docs/schemas\n";
$logFile = $tmp . '/semantic-log.json';
file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
(new SemanticLogValidator())->validate($logFile, $projectDir . '/docs/schemas');

// Clean up the scratch directory (clearDirectory removes the ownership marker too).
unlink($logFile);
if ($sqlSupported) {
    unlink($dbFile);
}

FileBodyStore::clearDirectory($tmp . '/bodies');
rmdir($tmp . '/bodies');
rmdir($tmp);
