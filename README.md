# BEAR.EventSourcing

Make your application's state changes a **replayable source of truth** — fold the events back to reconstruct state at any point in time.

Every event is a resource operation: a `method` on a `uri`, like `POST app://self/users`. That resource shape keeps replay straightforward, and it lets the same event stream double as an audit history of what happened, when, and to which resource.

## Installation

```bash
composer require bear/event-sourcing
```

## Quick start

Flush a Semantic Logger log, extract events, and iterate them:

```php
use BEAR\EventSourcing\SemanticLogExtractor;

$log = $semanticLogger->flush(); // Koriym\SemanticLogger\LogJson
$events = (new SemanticLogExtractor())->extract($log);

foreach ($events as $event) {
    // $event->uri, $event->method, $event->params, $event->result, $event->timestamp
    echo $event->method, ' ', $event->uri, "\n";
}
```

`SemanticLogExtractor` implements `SemanticLogExtractorInterface`, so it can be injected. Install `EventSourcingModule` and pass `RecordedMethods` only when the extraction policy differs from the default:

```php
use BEAR\EventSourcing\Module\EventSourcingModule;
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

## How it works

Semantic Logger is the observation source. The package reads a flushed Semantic Logger log and derives immutable events from its public open/close tree — it adds no event-dispatch code to your domain and persists nothing on its own.

```text
Semantic Logger observations -> Events -> optional EventStore
```

## What is an event

An `Event` is a successful, state-changing, resource-like operation observed in a Semantic Logger open/close pair. It carries only the facts it observed:

| Field | Source |
| --- | --- |
| `uri` | request `uri` |
| `method` | request `method` (upper-cased) |
| `params` | request `params`, falling back to `query` |
| `timestamp` | request `timestamp`, falling back to extraction time when missing or unparsable |
| `result` | `close.context.body` when present |

Recorded methods by default — `GET` is observation data, not a state change, so it is ignored:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

Include `GET` explicitly for development-time read tracing by injecting `new RecordedMethods(RecordedMethods::WITH_READS)`.

A response `code` of `400` or greater marks a failed operation, which is ignored; a numeric-string code such as `"404"` is read the same way. A missing code is treated as success (no failure signal). A code that is present but cannot be read as a status — a non-numeric string, a float, a boolean — is treated as a failure too, so only confirmed successes become events.

## Filtering and replay

`Events` is a countable, iterable collection. Keep it small and select with PHP's standard iterators instead of adding query methods — filters stack without changing the collection:

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

URI prefixes and timestamps work the same way:

```php
$orderEvents = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => str_starts_with($event->uri, 'app://self/orders/123'),
);
```

See `examples/extract.php`, `examples/replay.php`, and `examples/store.php` for runnable end-to-end scripts.

## Storage (optional)

Persist extracted events explicitly when an application needs storage. `EventStoreInterface` is a small persistence port (`append`, `appendAll`, `all`), not a runtime hook.

Use `InMemoryEventStore` for tests and development:

```php
use BEAR\EventSourcing\Store\InMemoryEventStore;

$store = new InMemoryEventStore();
$store->appendAll($events);
```

Use `MediaQueryEventStore` when the EventStore should be backed by SQL through Ray.MediaQuery. MediaQuery stays application-owned; `EventSourcingModule` only adds the EventStore binding and never hides `AuraSqlModule` or `MediaQuerySqlModule`:

```php
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Module\MediaQueryEventStoreModule;
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

Apply `sql/event_store/schema.sql` with your application's migration tool before using the SQL store. `MediaQueryEventStore` keeps JSON and timestamp database mapping inside the adapter, not on `Event`.

A few operational notes:

- **Already using Ray.MediaQuery?** `#[SqlDir]` binds to a single directory, so installing a second `MediaQuerySqlModule` for the package path conflicts with your own. Copy `sql/event_store/*.sql` into your application's `sqlDir` (and point `interfaceDir` at a directory that also contains `EventStoreQueryInterface`) instead of adding a separate module.
- **`appendAll` is not atomic.** It appends events one row at a time with no surrounding transaction, so a mid-way failure leaves earlier rows written. The database is application-owned, so wrap a batch in your own transaction when you need all-or-nothing — this is also markedly faster, since one commit replaces one fsync per event:

  ```php
  $pdo->beginTransaction();
  $store->appendAll($events);
  $pdo->commit();
  ```

- **`params` must be a string-keyed map.** `append()` rejects an `Event` whose `params` has non-string keys with an `EventStoreException`, failing fast rather than storing a row that a later `all()` cannot restore.

## BEAR.Resource observation bridge (optional)

To produce Semantic Logger open/close entries from BEAR.Resource execution, decorate `InvokerInterface` — not `LoggerInterface`. `ResourceObservationModule` wraps an existing BEAR.Resource module:

```php
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new ResourceObservationModule(
    module: new ResourceClientModule(),
));
```

By default the bridge installs `NullBodyStore`, so no body is stored. Applications that need payload inspection can provide their own `BodyStoreInterface` and store the body in files, SQL, object storage, or test fixtures.

For local AI/debug work, use `DevLogModule`. It clears the body directory when the injector is created, stores rendered bodies as files through `FileBodyStore`, and records `GET` as well as write methods:

```php
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new DevLogModule(
    bodyDir: __DIR__ . '/var/es/bodies',
    module: new ResourceClientModule(),
));
```

A `BodyStoreInterface` records a `body_ref` in the close context:

```json
{"code": 200, "body_ref": "file:///path/to/var/es/bodies/000001.json"}
```

`body_ref` is a reference to a stored rendered body. It stays in the Semantic Log for inspection and is **not** extracted into `Event::$result` — the event's `result` comes from `close.context.body`. A bridge log that records only `body_ref` therefore yields an event with a `null` result; the payload lives in the externalized body, not in the event. The same domain operation produces the same event regardless of which `BodyStoreInterface` the bridge uses.

### What dev observation produces

With `DevLogModule` active you read two artifacts:

**Body files** under `bodyDir`, one per recorded operation, numbered in invocation order. The directory is cleared when the injector is created, so it always reflects the latest run:

```text
var/es/bodies/000001.json   # rendered body of the first recorded operation, i.e. $ro->toString()
var/es/bodies/000002.json
```

`bodyDir` must be a dedicated directory owned by the body store: it is cleared on each run, and to avoid deleting anything else it refuses to clear a directory it did not create or adopt while empty (an ownership marker guards this). Use one `bodyDir` per process; the sequence counter is per-store, so pointing concurrent processes at the same directory can overwrite each other's files. A failed render is recorded as an `exception` in the close context, not written as an empty body file.

**The Semantic Logger log**, a nested open/close tree held in memory until you call `$logger->flush()`. Render it as a readable tree — far smaller than the raw JSON, for both humans and AI. `Resource\Stree\ResourceNodeFormatter` renders each node as one resource operation, so a `POST app://self/orders` that internally calls `PUT app://self/inventory/SKU-1` reads as intent in, result out:

```text
request="POST app://self/orders?order_id=O-1000"
├── request="PUT app://self/inventory/SKU-1?sku=SKU-1&quantity=1"
│   ├── media_query name=inventory_reserve sku=SKU-1
│   │   └── rows_ref=file://var/es/rows/000001.json
│   └── code=200 body_ref=file://var/es/bodies/000001.json
└── code=201 body_ref=file://var/es/bodies/000002.json
```

The request line is the intent (`method` on a `uri` with its params as a query string); the `└──` close line is the result (`code` plus the `body_ref` pointer). Child operations nest under their parent — a resource calling a resource, and a resource embedding a non-resource operation such as a media query, which renders in stree's generic form but stays structurally clear. Every node follows one rule: the intent is inline, the heavy detail sits behind a `*_ref` pointer (`body_ref`, `rows_ref`). The resource shape keeps the tree normalized, and no timestamp noise leaks in. When debugging, follow a node's `*_ref` to its file for the full detail.

Render it with `TreeRenderer` and a `FormatterRegistry` that registers `ResourceNodeFormatter` for the `resource_request` type — `examples/tree.php` builds a `DevLogModule`-style log (`body_ref` pointers) and renders it, and `examples/semantic-tree.txt` is its output. The bundled `vendor/bin/stree dev-log.json` works too, but renders the generic form (type label plus a raw `timestamp`) since the CLI does not load custom formatters. This package never writes the log to disk itself; `examples/semantic-log.json` is the raw `LogJson` of the extraction examples.

## Boundaries

- Semantic Logger is the observation source; EventStore is an optional destination.
- No automatic persistence during runtime observation.
- No `BEAR\Resource\LoggerInterface` decorator.
- Ray.MediaQuery and database installation stay in the application, never hidden inside EventSourcing modules.
