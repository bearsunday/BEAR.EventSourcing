# BEAR.EventSourcing (WIP)

Event Sourcing for BEAR.Sunday — recording completed state-changing resource responses as immutable events.

## Philosophy

BEAR.Sunday does not provide Event Sourcing as a feature. By following REST and BEAR.Resource logging constraints, event recording can be added without making resources aware of the event store.

> "A framework is a constraint, not a solution."

## Concept

The current implementation decorates BEAR.Resource's `LoggerInterface`, observes completed resource responses, and appends state-change events directly to the configured `EventStoreInterface`.

Semantic Logger is optional. The `vendor-slogger/` bridge can convert BEAR.Resource logger calls into Koriym SemanticLogger contexts, and `Events::fromSemanticLog()` can extract events from a flushed semantic log. That path is an import/extraction bridge, not the runtime source of truth for `EventSourcingLogger`.

```
BEAR.Resource LoggerInterface -> EventSourcingLogger -> EventStore
```

Optional bridge:

```
BEAR.Resource LoggerInterface -> Semantic Logger -> Events::fromSemanticLog()
```

| Layer | Role | Persistence |
|-------|------|-------------|
| BEAR.Resource LoggerInterface | Completed resource response hook | Existing app logger |
| EventSourcingLogger | Converts successful write responses into events | None |
| Event | Immutable state-change fact | Permanent |
| EventStore | Appends and queries recorded events | Replaceable |
| Semantic Logger bridge | Optional semantic-log import/export path | Configurable |

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

`EventSourcingLogger` records successful state-changing resource requests as events.

Recorded methods:

- `post` -> `POST`
- `put` -> `PUT`
- `delete` -> `DELETE`

`get` and `patch` are not recorded by `EventSourcingLogger`.

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

Event Sourcing extracts one aspect of completed resource responses: **state changed**.

Replay needs facts. Analysis may need richer context. The optional Semantic Logger bridge can supply that context; Event Sourcing keeps only the fact stream needed for replay.

## Semantic Logger bridge

The `vendor-slogger/` path package adapts `BEAR\Resource\LoggerInterface` calls into Koriym SemanticLogger request/response contexts. `Events::fromSemanticLog()` can convert a flushed semantic log into an `Events` collection:

```php
$events = Events::fromSemanticLog($semanticLogger->flush()->toArray());
```

This bridge reads semantic logs; it does not make `EventSourcingLogger` observe Semantic Logger, and it does not append to `EventStoreInterface` by itself.

The bridge recognizes `POST`, `PUT`, `PATCH`, and `DELETE` entries present in the semantic log. The root `EventSourcingLogger` records only `POST`, `PUT`, and `DELETE`.
