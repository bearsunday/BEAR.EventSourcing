# BEAR.EventSourcing

Event sourcing primitives extracted from Semantic Logger observations.

## Intent

This project starts from one constraint: **Semantic Logger is the observation source**.

The core package reads Semantic Logger observations and derives immutable event facts from them. BEAR.Resource integration is an optional Invoker bridge that writes observations to Semantic Logger; it does not decorate `BEAR\Resource\LoggerInterface`, and it does not persist events during resource execution.

```text
Semantic Logger observations -> Events -> optional EventStore
```

## API

The package provides:

- `Event`: one immutable state-change fact
- `EventsInterface` / `Events`: countable, iterable event collection
- `SemanticLogExtractorInterface` / `SemanticLogExtractor`: extract events from a flushed Semantic Logger log
- `RecordedMethods`: injectable extraction policy for recorded methods
- `EventStoreInterface`: persistence port for extracted events
- `InMemoryEventStore`: in-memory EventStore implementation for tests and development
- `MediaQueryEventStore`: Ray.MediaQuery SQL EventStore implementation
- `EventSourcingModule`: optional Ray.Di bindings for extraction and store selection
- `MediaQueryEventStoreModule`: EventStore binding for an existing Ray.MediaQuery setup
- `Resource\ResourceObservationModule`: optional BEAR.Resource Invoker observation bridge
- `Resource\ViewStoreInterface`: optional view reference store used by the observation bridge
- `Resource\FileViewStore`: opt-in development view store that writes views to files
- `Resource\DevLogModule`: development module that clears the view directory at bootstrap and records reads

Persistence and framework integration are explicit application choices, not automatic runtime behavior.

## Event boundary

An event represents a successful state-changing resource-like operation observed in a Semantic Logger open/close pair.

Recorded methods by default:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

`GET` is observation data by default. Applications can include it explicitly for development-time read tracing by injecting a different `RecordedMethods` policy.

## Usage

Extract events from a flushed Semantic Logger log through an injected extractor:

```php
use BEAR\EventSourcing\SemanticLogExtractorInterface;

$log = $semanticLogger->flush();

/** @var SemanticLogExtractorInterface $extractor */
$events = $extractor->extract($log);

foreach ($events as $event) {
    // append to an EventStore, project, replay, or inspect
}
```

Configure the extractor through dependency injection. Install `EventSourcingModule` and pass `RecordedMethods` only when the extraction policy differs from the default:

```php
use BEAR\EventSourcing\EventSourcingModule;
use BEAR\EventSourcing\RecordedMethods;
use Ray\Di\AbstractModule;

final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new EventSourcingModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
        ));
    }
}
```

Observe BEAR.Resource execution by decorating `InvokerInterface`, not `LoggerInterface`. The module wraps an existing BEAR.Resource module and emits Semantic Logger open/close entries:

```php
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new ResourceObservationModule(
    module: new ResourceClientModule(),
));
```

By default the bridge installs `NullViewStore`, so no view is rendered or saved. Applications that need payload inspection can provide their own `ViewStoreInterface` and store the view in files, SQL, object storage, or test fixtures.

For local AI/debug work, use the development module. It clears the view directory when the injector is created, stores rendered views as files, and records `GET` as well as write methods:

```php
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new DevLogModule(
    viewDir: __DIR__ . '/var/es/views',
    module: new ResourceClientModule(),
));
```

The log keeps only the reference:

```json
{"code": 200, "view_ref": "file:///path/to/var/es/views/000001.json"}
```

The extractor accepts Semantic Logger `LogJson` and reads its public `open` tree. Each recorded operation has a context with:

- `uri`: resource-like identifier
- `method`: HTTP-style method
- `params` or `query`: operation input
- `timestamp`: optional ISO-8601 timestamp

The matching `close.context.body` becomes the event result. If `close.context.code` exists and is `400` or greater, the operation is treated as unsuccessful and ignored.

For BEAR.Resource observation, the close context records `code` and, when a `ViewStoreInterface` is configured, a `view_ref`:

```json
{"code": 200, "view_ref": "file://var/es/views/000001.json"}
```

`view_ref` is a reference to a stored rendered view. It stays in the Semantic Log for inspection and is **not** extracted into `Event::$result` — the domain event keeps only the facts it observed (`uri`, `method`, `params`, `timestamp`), with `result` taken from `close.context.body` when present. A bridge log that records only `view_ref` therefore yields an event with a `null` result; the payload lives in the externalized view, not in the event. This keeps the same domain operation producing the same event regardless of which `ViewStoreInterface` the bridge uses.

Filter events with PHP's standard iterators. Iterator filters can be stacked without adding methods to `Events`:

```php
use BEAR\EventSourcing\Event;

$userEvents = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => ($event->params['id'] ?? null) === 'koriym',
);

$userWrites = new CallbackFilterIterator(
    $userEvents,
    static fn (Event $event): bool => $event->method !== 'GET',
);

foreach ($userWrites as $event) {
    // replay, project, or inspect events for id=koriym
}
```

URI prefixes and timestamps can be handled the same way:

```php
$orderEvents = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => str_starts_with($event->uri, 'app://self/orders/123'),
);
```

Persist extracted events explicitly when an application needs storage. Use `InMemoryEventStore` for tests and development:

```php
use BEAR\EventSourcing\InMemoryEventStore;

$store = new InMemoryEventStore();
$store->appendAll($events);
```

Use `MediaQueryEventStore` when the EventStore should be backed by SQL through Ray.MediaQuery. MediaQuery remains application-owned; the EventSourcing module only adds EventStore bindings:

```php
use BEAR\EventSourcing\EventSourcingModule;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\MediaQueryEventStoreModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\MediaQuerySqlModule;

final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $packageDir = __DIR__ . '/vendor/bear/event-sourcing';
        $this->install(new AuraSqlModule('sqlite:' . __DIR__ . '/events.sqlite'));
        $this->install(new MediaQuerySqlModule(
            interfaceDir: $packageDir . '/src/Query',
            sqlDir: $packageDir . '/sql/event_store',
        ));
        $this->install(new EventSourcingModule(
            store: new MediaQueryEventStoreModule(),
        ));
    }
}

$store = (new Injector(new AppModule()))->getInstance(EventStoreInterface::class);
$store->appendAll($events);
```

`EventSourcingModule` does not install `MediaQuerySqlModule`. If your application already uses Ray.MediaQuery, keep that configuration in the application and add the EventStore query interface and SQL statements to that MediaQuery setup. Apply `sql/event_store/schema.sql` with your application's migration tool before using the SQL store.

`EventStoreInterface` is intentionally small. It is a persistence port for already-extracted events, not a runtime hook.
`MediaQueryEventStore` keeps JSON and timestamp database mapping inside the adapter, not on `Event`.
