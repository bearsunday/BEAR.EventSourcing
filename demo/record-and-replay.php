<?php

/**
 * End-to-end demo of the three-layer pipeline:
 *
 *   1. Users resource is POSTed twice
 *   2. BEAR\SemanticLogger\SemanticLogger bridge records each call into Koriym SemanticLogger
 *   3. flush() → Events::fromSemanticLog() extracts the state-change events
 *   4. EventStore persists them (SQLite in-memory for this demo)
 *   5. Later: read back from the store, reset the world, and replay
 *   6. The replay reproduces the original results — proving idempotent replay
 *
 * Run: php demo/record-and-replay.php
 */

declare(strict_types=1);

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Demo\Users;
use BEAR\EventSourcing\EventStore;
use BEAR\EventSourcing\Events;
use BEAR\Resource\Uri;
use BEAR\SemanticLogger\SemanticLogger as Bridge;
use Koriym\SemanticLogger\SemanticLogger;

require __DIR__ . '/../vendor/autoload.php';

$semanticLogger = new SemanticLogger();
$bridge = new Bridge($semanticLogger);

$pdo = new ExtendedPdo('sqlite::memory:');
$pdo->exec(<<<'SQL'
    CREATE TABLE event_store (
        id TEXT PRIMARY KEY,
        timestamp TEXT NOT NULL,
        uri TEXT NOT NULL,
        method TEXT NOT NULL,
        params TEXT,
        result TEXT
    )
SQL);
$eventStore = new EventStore($pdo);

/**
 * Simulate one POST /users call, recording via the bridge.
 *
 * @param array{name: string, age: int} $params
 */
$callPost = function (Bridge $bridge, array $params): Users {
    $users = new Users();
    $users->uri = new Uri('app://self/users');
    $users->uri->method = 'post';
    $users->uri->query = $params;
    $users->onPost(...$params);
    $bridge($users);

    return $users;
};

echo "=== Phase 1: live calls ===\n";
$first = $callPost($bridge, ['name' => 'Alice', 'age' => 30]);
$second = $callPost($bridge, ['name' => 'Bob', 'age' => 25]);

$originalResults = [$first->body, $second->body];
echo "Original results:\n", json_encode($originalResults, JSON_PRETTY_PRINT), "\n\n";

echo "=== Phase 2: extract events from SemanticLog ===\n";
$log = $semanticLogger->flush();
$events = Events::fromSemanticLog($log->toArray());
echo sprintf("Extracted %d events from the SemanticLog tree.\n\n", count($events));

echo "=== Phase 3: persist ===\n";
foreach ($events as $event) {
    $eventStore->append($event);
}

echo "Persisted. JSON snapshot (first event):\n";
echo json_encode($events->all()[0]->toArray(), JSON_PRETTY_PRINT), "\n\n";

echo "=== Phase 4: read back from a fresh store handle ===\n";
$stored = $eventStore->getEventsByUri('app://self/users*');
echo sprintf("Read back %d events.\n\n", count($stored));

echo "=== Phase 5: reset world, replay ===\n";
Users::reset();

$replayResults = [];
$stored->replay(function ($event) use (&$replayResults): void {
    $users = new Users();
    $users->uri = new Uri($event->uri);
    $users->uri->method = strtolower($event->method);
    $users->uri->query = $event->params;
    $users->{'on' . ucfirst(strtolower($event->method))}(...$event->params);
    $replayResults[] = $users->body;
});

echo "Replay results:\n", json_encode($replayResults, JSON_PRETTY_PRINT), "\n\n";

echo "=== Phase 6: verify ===\n";
$ok = $originalResults === $replayResults;
echo "Replay matches originals: ", $ok ? "YES" : "NO", "\n";

exit($ok ? 0 : 1);
