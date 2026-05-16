# BEAR.EventSourcing

Event Sourcing library for BEAR.Sunday.

## Installation

```bash
composer require bear/event-sourcing
```

## Usage

### Module Installation

```php
use BEAR\EventSourcing\Module\EventSourcingModule;

class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new EventSourcingModule());
    }
}
```

### Recording Events

All state-changing operations (POST, PUT, DELETE) are automatically recorded as events via AOP interceptor.

```php
// Events are stored with:
{
    "id": "uuid",
    "timestamp": "2025-01-01 12:00:00.000000",
    "uri": "/products/1",
    "method": "PUT",
    "params": {"name": "Updated Product"},
    "result": {"id": 1, "name": "Updated Product"}
}
```

### Querying Events

```php
use BEAR\EventSourcing\EventStoreInterface;

class MyService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}

    public function getHistory(): void
    {
        // Get all events
        $events = $this->eventStore->getEvents();

        // Get events since timestamp
        $events = $this->eventStore->getEventsSince(new DateTime('2025-01-01'));

        // Get events by URI pattern
        $events = $this->eventStore->getEventsByUri('/orders/*');

        // Replay events
        $events->replay(function(Event $e) {
            echo "Event: {$e->uri} {$e->method}\n";
        });
    }
}
```

## License

MIT License
