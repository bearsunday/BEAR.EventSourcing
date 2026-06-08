# BEAR.EventSourcing (WIP)

Event Sourcing for BEAR.Sunday — extracting state-change facts from semantic observations.

## Philosophy

BEAR.Sunday does not provide Event Sourcing as a feature. By following REST and semantic observation constraints, event recording can be added without making resources aware of the event store.

> "A framework is a constraint, not a solution."

## Concept

Semantic Logger is the observation layer. BEAR.EventSourcing extracts state-change facts from those observations and stores them as immutable events.

The current implementation decorates BEAR.Resource's `LoggerInterface`, observing completed resource responses without making resources aware of the event store.

```
Semantic observation -> Event -> EventStore
```

| Layer | Role | Persistence |
|-------|------|-------------|
| Semantic Logger | Records meaningful observations | Configurable |
| Event | Immutable state-change fact | Permanent |
| EventStore | Appends and queries recorded events | Replaceable |

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

`EventStoreInterface` is the persistence port. The bundled `SqlEventStore` implementation is SQL-backed and delegates SQL execution to Ray.MediaQuery through `BEAR\EventSourcing\Query\EventStoreQueryInterface` and `BEAR\EventSourcing\Query\EventStoreCommandInterface`. `InMemoryEventStore` is available for tests and transient use, and applications can replace the store with KVS or another append-only storage implementation.

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

`EventSourcingLogger` records successful state-changing resource requests as semantic observations.

Recorded methods:

- `post` -> `POST`
- `put` -> `PUT`
- `delete` -> `DELETE`

`get` and `patch` are not recorded.

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

When the request succeeds, the logger appends an event with the resource URI, HTTP method, URI query, and result body. URI query is the canonical resource method input.

### Why Logger?

| Approach | Cost |
|----------|------|
| Logger | Completed resource responses |
| AOP | Resource method weaving |
| Invoker | All resource requests |

Benefits:

- Resources remain unaware of Event Sourcing
- Failed calls are not recorded
- GET and PATCH requests are excluded
- Existing resource logging is preserved through Ray.Di `rename()`
- No global state

## Module

```php
use BEAR\EventSourcing\EventSourcing\Events;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\EventSourcing\SqlEventStore;
use BEAR\EventSourcing\Logger\EventSourcingLogger;
use BEAR\Resource\LoggerInterface as ResourceLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class EventSourcingModule extends AbstractModule
{
    private const LOGGER = 'event_sourcing_logger';

    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)->to(SqlEventStore::class);
        $this->bind(EventsInterface::class)->to(Events::class);

        $this->rename(ResourceLoggerInterface::class, self::LOGGER);
        $this->bind(ResourceLoggerInterface::class)
            ->toConstructor(EventSourcingLogger::class, ['logger' => self::LOGGER])
            ->in(Scope::SINGLETON);
    }
}
```

Install it as a wrapping module so `rename()` can move the existing BEAR.Resource logger:

```php
$module = new EventSourcingModule($appModule);
```

### MediaQuery Setup

`EventSourcingModule` does not install Ray.MediaQuery. Configure it in the application module, using the same SQL root the application already uses, and include the event store query interfaces in the MediaQuery query set.

Place the bundled SQL files under the application's MediaQuery SQL root:

```
sql/
  event_store/
    append.sql
    create_mysql_table.sql
    create_sqlite_method_index.sql
    create_sqlite_table.sql
    create_sqlite_timestamp_index.sql
    create_sqlite_uri_index.sql
    list.sql
    list_by_aggregate_id.sql
    list_by_uri.sql
    list_since.sql
```

Then include the event store interfaces in the application's MediaQuery configuration:

```php
use BEAR\EventSourcing\Query\EventStoreCommandInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

final class AppMediaQueryModule extends AbstractModule
{
    private const SQL_DIR = __DIR__ . '/../../sql';

    protected function configure(): void
    {
        $queries = Queries::fromClasses([
            EventStoreQueryInterface::class,
            EventStoreCommandInterface::class,
            // App\Query\ArticleQueryInterface::class,
            // App\Query\UserQueryInterface::class,
        ]);

        $this->install(new MediaQueryModule($queries, [new DbQueryConfig(self::SQL_DIR)]));
    }
}
```

Do not install a second `MediaQuerySqlModule` with a different SQL directory just for event sourcing in an application that already uses MediaQuery. `SqlQuery` resolves SQL from the active `SqlDir` binding, so the event store SQL should live in that same root.

For tests, replace the persistence adapter:

```php
$this->bind(EventStoreInterface::class)->to(InMemoryEventStore::class);
```

## Design Principles

| Principle | Application |
|-----------|-------------|
| WYSIWYG | The completed resource method result is recorded |
| Single Responsibility | EventStore stores and retrieves events |
| Symmetry | `fromJson` / `toJson` |
| Transparency | Resources are unaware of Event Sourcing |
| No Global State | DI injection, no global registry |

## Vision

Event Sourcing extracts one aspect of semantic observation: **state changed**.

Replay needs facts. Analysis may need richer context. Semantic Logger supplies that context; Event Sourcing keeps only the fact stream needed for replay.


## Semantic Logger bridge

This branch also includes a `vendor-slogger/` path package that adapts `BEAR\Resource\LoggerInterface` events into Koriym SemanticLogger contexts. `Events::fromSemanticLog()` can extract state-change events from that semantic log while keeping the current EventSourcing store implementation.


## EC-CUBE port prototype

PR #2 adds an EC-CUBE-inspired application prototype under the domain, query, resource, service, authentication, and validation namespaces. The EventSourcing core implementation remains the current `src/EventSourcing/*` implementation from `1.x`; the prototype code is kept as application-layer material on top of that core.

The repository quality gates continue to target the EventSourcing core and its tests. The EC-CUBE application-layer prototype is retained for follow-up integration without replacing the core implementation.
