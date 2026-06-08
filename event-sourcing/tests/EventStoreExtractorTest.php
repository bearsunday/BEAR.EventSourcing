<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use BEAR\EventSourcing\Fake\FakeCompleteContext;
use BEAR\EventSourcing\Fake\FakeOpenContext;
use PHPUnit\Framework\TestCase;

final class EventStoreExtractorTest extends TestCase
{
    private InMemoryEventStore $store;
    private EventStoreExtractor $extractor;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore();
        $this->extractor = new EventStoreExtractor($this->store);
    }

    public function testExtractPostRequest(): void
    {
        $open = new FakeOpenContext('POST', 'app://self/user', ['name' => 'John']);
        $complete = new FakeCompleteContext('app://self/user', 201, [], ['id' => 1, 'name' => 'John']);

        $this->extractor->extract($open, $complete);

        $events = $this->store->getAllEvents();
        $this->assertCount(1, $events);

        $event = $events->toArray()[0];
        $this->assertSame('app://self/user', $event->uri);
        $this->assertSame('POST', $event->method);
        $this->assertSame(['name' => 'John'], $event->params);
    }

    public function testExtractPutRequest(): void
    {
        $open = new FakeOpenContext('PUT', 'app://self/user', ['id' => 1, 'name' => 'Jane']);
        $complete = new FakeCompleteContext('app://self/user', 200, [], ['id' => 1, 'name' => 'Jane']);

        $this->extractor->extract($open, $complete);

        $events = $this->store->getAllEvents();
        $this->assertCount(1, $events);
    }

    public function testExtractDeleteRequest(): void
    {
        $open = new FakeOpenContext('DELETE', 'app://self/user', ['id' => 1]);
        $complete = new FakeCompleteContext('app://self/user', 204, [], null);

        $this->extractor->extract($open, $complete);

        $events = $this->store->getAllEvents();
        $this->assertCount(1, $events);
    }

    public function testSkipsGetRequest(): void
    {
        $open = new FakeOpenContext('GET', 'app://self/user', ['id' => 1]);
        $complete = new FakeCompleteContext('app://self/user', 200, [], ['id' => 1, 'name' => 'John']);

        $this->extractor->extract($open, $complete);

        $events = $this->store->getAllEvents();
        $this->assertCount(0, $events);
    }

    public function testSkipsGetRequestCaseInsensitive(): void
    {
        $open = new FakeOpenContext('get', 'app://self/user', ['id' => 1]);
        $complete = new FakeCompleteContext('app://self/user', 200, [], ['id' => 1]);

        $this->extractor->extract($open, $complete);

        $events = $this->store->getAllEvents();
        $this->assertCount(0, $events);
    }

    /** @return list<array{0: string}> */
    public static function safeMethodsProvider(): array
    {
        return [['HEAD'], ['OPTIONS'], ['TRACE']];
    }

    /** @dataProvider safeMethodsProvider */
    public function testSkipsSafeMethod(string $method): void
    {
        $open = new FakeOpenContext($method, 'app://self/user', ['id' => 1]);
        $complete = new FakeCompleteContext('app://self/user', 200, [], null);

        $this->extractor->extract($open, $complete);

        $this->assertCount(0, $this->store->getAllEvents());
    }
}
