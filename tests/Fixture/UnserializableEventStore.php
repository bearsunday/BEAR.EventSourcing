<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventStoreInterface;
use Closure;
use Override;

/** A store holding what PHP cannot serialize, as a store holding a PDO does */
final class UnserializableEventStore implements EventStoreInterface
{
    private Closure $connection;

    public function __construct()
    {
        $this->connection = static fn (): bool => true;
    }

    #[Override]
    public function append(Event $event): void
    {
        ($this->connection)();
    }

    #[Override]
    public function appendAll(EventsInterface $events): void
    {
        ($this->connection)();
    }

    #[Override]
    public function all(): EventsInterface
    {
        return new Events([]);
    }
}
