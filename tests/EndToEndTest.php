<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Demo\Users;
use BEAR\Resource\Uri;
use BEAR\SemanticLogger\SemanticLogger as Bridge;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the full three-layer pipeline.
 *
 * Mirrors demo/record-and-replay.php so CI catches breakage in either path
 * without depending on running the demo script as a separate process.
 */
final class EndToEndTest extends TestCase
{
    public function testRecordExtractPersistReadReplay(): void
    {
        Users::reset();

        $koriymLogger = new SemanticLogger();
        $bridge = new Bridge($koriymLogger);

        // Phase 1: live calls
        $first = $this->postUser($bridge, ['name' => 'Alice', 'age' => 30]);
        $second = $this->postUser($bridge, ['name' => 'Bob', 'age' => 25]);
        $originalResults = [$first->body, $second->body];

        // Phase 2: extract
        $events = Events::fromSemanticLog($koriymLogger->flush()->toArray());
        $this->assertCount(2, $events);

        // Phase 3: persist
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
        $store = new EventStore($pdo);
        foreach ($events as $event) {
            $store->append($event);
        }

        // Phase 4: read back
        $stored = $store->getEventsByUri('app://self/users*');
        $this->assertCount(2, $stored);

        // Phase 5: reset world, replay
        Users::reset();
        $replayResults = [];
        $stored->replay(function (Event $event) use (&$replayResults): void {
            $users = new Users();
            $users->uri = new Uri($event->uri);
            $users->uri->method = strtolower($event->method);
            $users->uri->query = $event->params;
            $users->onPost(...$event->params);
            $replayResults[] = $users->body;
        });

        // Phase 6: verify
        $this->assertSame($originalResults, $replayResults);
    }

    /**
     * @param array{name: string, age: int} $params
     */
    private function postUser(Bridge $bridge, array $params): Users
    {
        $users = new Users();
        $users->uri = new Uri('app://self/users');
        $users->uri->method = 'post';
        $users->uri->query = $params;
        $users->onPost(...$params);
        $bridge($users);

        return $users;
    }
}
