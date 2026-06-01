# BEAR.EventSourcing (WIP)

Event Sourcing for BEAR.Sunday — extracting state-change facts from semantic observations.

## Philosophy

BEAR.Sunday does not provide Event Sourcing as a feature. By following REST, AOP, and semantic observation constraints, event recording can be added without making resources aware of the event store.

> "A framework is a constraint, not a solution."

## Concept

Semantic Logger is the observation layer. BEAR.EventSourcing extracts state-change facts from those observations and stores them as immutable events.

The current implementation observes completed `ResourceObject` method calls through AOP. A richer Semantic Logger can provide the same observation boundary without changing the event model.

```
Semantic observation -> Event -> EventStore
```

| Layer | Role | Persistence |
|-------|------|-------------|
| Semantic Logger | Records meaningful observations | Configurable |
| Event | Immutable state-change fact | Permanent |
| EventStore | Appends and queries recorded events | Database-backed |

## Installation

```bash
composer require bear/event-sourcing
```

## Interfaces

### Event

Immutable fact of a state change:

```php
final readonly class Event implements JsonSerializable
{
    public static function create(
        string $uri,
        string $method,
        array $params,
        mixed $result,
    ): self;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### EventsInterface

```php
interface EventsInterface extends IteratorAggregate, Countable
{
    public static function fromJson(string $json): self;
    public function toJson(): string;
    public function add(Event $event): self;
    public function filterByUri(string $pattern): self;
    public function filterByMethod(string $method): self;
    public function since(DateTimeInterface $since): self;
    public function replay(callable $handler): void;
    public function all(): array;
}
```

### EventStoreInterface

```php
interface EventStoreInterface
{
    public function append(Event $event): void;
    public function getEvents(): EventsInterface;
    public function getEventsSince(DateTimeInterface $since): EventsInterface;
    public function getEventsByUri(string $pattern): EventsInterface;
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface;
}
```

## Usage

### Create and Store

```php
$event = Event::create('/users/1', 'POST', ['name' => 'John'], ['id' => 1]);

$eventStore->append($event);
```

### Query

```php
$events = $eventStore->getEventsByUri('/users/*');

foreach ($events as $event) {
    // process event
}
```

### Replay

```php
$events->replay(function (Event $event) use ($resource): void {
    $resource->{$event->method}($event->uri, $event->params);
});
```

### Serialize

```php
// Export
file_put_contents('events.json', $events->toJson());

// Import and replay
Events::fromJson(file_get_contents('events.json'))->replay($handler);
```

### Integration Testing

Replay production events for regression testing:

```php
$events = Events::fromJson(file_get_contents('production-events.json'));

$events->replay(function (Event $event) use ($resource, $test): void {
    $result = $resource->{$event->method}($event->uri, $event->params);
    $test->assertSame($event->result, $result->body);
});
```

Useful for:

- Bug reproduction
- Regression testing
- Production data verification

Replay assumes idempotent handlers.

## Recording

`EventSourcingInterceptor` records successful state-changing resource method calls as semantic observations.

Recorded methods:

- `onPost` -> `POST`
- `onPut` -> `PUT`
- `onPatch` -> `PATCH`
- `onDelete` -> `DELETE`

`onGet` is not recorded.

```php
final class User extends ResourceObject
{
    public function onPost(string $name): static
    {
        $this->body = ['id' => 1, 'name' => $name];

        return $this;
    }
}
```

When the method succeeds, the interceptor appends an event with the resource URI, HTTP method, method parameters, and result body. A Semantic Logger integration can replace or enrich this observation step while preserving the same `Event` representation.

### Why AOP?

| Approach | Cost |
|----------|------|
| AOP | Only bound resource methods |
| Invoker | All requests |

Benefits:

- Resources remain unaware of Event Sourcing
- Failed calls are not recorded
- GET requests are excluded
- No global state

## Module

```php
class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)->to(EventStore::class);
        $this->bind(EventsInterface::class)->to(Events::class);

        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->logicalOr(
                $this->matcher->startsWith('onPost'),
                $this->matcher->logicalOr(
                    $this->matcher->startsWith('onPut'),
                    $this->matcher->logicalOr(
                        $this->matcher->startsWith('onPatch'),
                        $this->matcher->startsWith('onDelete'),
                    ),
                ),
            ),
            [EventSourcingInterceptor::class],
        );
    }
}
```

## Design Principles

| Principle | Application |
|-----------|-------------|
| WYSIWYG | The completed resource method result is recorded |
| Single Responsibility | EventStore stores and retrieves events |
| Symmetry | `fromJson` / `toJson` |
| Transparency | Resources are unaware of Event Sourcing |
| No Global State | DI injection, no order dependency |

## Vision

Event Sourcing extracts one aspect of semantic observation: **state changed**.

Replay needs facts. Analysis may need richer context. Semantic Logger supplies that context; Event Sourcing keeps only the fact stream needed for replay.
