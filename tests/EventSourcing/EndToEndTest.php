<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use BEAR\EventSourcing\Demo\Users;
use BEAR\Resource\Uri;
use BEAR\SemanticLogger\SemanticLogger as Bridge;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function is_int;
use function is_string;
use function strtolower;

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
        $store = new InMemoryEventStore();
        foreach ($events as $event) {
            $store->append($event);
        }

        // Phase 4: read back
        $stored = $store->getEventsByUri('app://self/users*');
        $this->assertCount(2, $stored);

        // Phase 5: reset world, replay
        Users::reset();
        $replayResults = [];
        $stored->replay(static function (Event $event) use (&$replayResults): void {
            $name = $event->params['name'] ?? null;
            $age = $event->params['age'] ?? null;
            if (! is_string($name) || ! is_int($age)) {
                throw new UnexpectedValueException('Replay event params must contain a string name and int age.');
            }

            $users = new Users();
            $users->uri = new Uri($event->uri);
            $users->uri->method = strtolower($event->method);
            $users->uri->query = ['name' => $name, 'age' => $age];
            $users->onPost($name, $age);
            $replayResults[] = $users->body;
        });

        // Phase 6: verify
        $this->assertSame($originalResults, $replayResults);
    }

    /** @param array{name: string, age: int} $params */
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
