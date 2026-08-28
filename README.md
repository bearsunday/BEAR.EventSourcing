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
    // $event->id, $event->uri, $event->method, $event->params, $event->result, $event->timestamp
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

An `Event` is a successful, state-changing resource operation observed in a Semantic Logger open/close pair of type `resource_request`. It carries only the facts it observed:

| Field | Source |
| --- | --- |
| `id` | derived from the observed facts (see below) |
| `uri` | request `uri` |
| `method` | request `method` (upper-cased) |
| `params` | request `params`, falling back to `query` |
| `timestamp` | request `timestamp` — an entry without one is not extracted |
| `result` | `close.context.body` when present |

Extraction is deterministic: it never invents facts. There is no fallback clock for a missing `timestamp`, and only entries of type `resource_request` qualify — another subsystem's open/close pair that happens to carry `method` and `uri` fields is never misread as a state change. Extracting the same log twice yields the same events.

Recorded methods by default — `GET` is observation data, not a state change, so it is ignored:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

Include `GET` explicitly for development-time read tracing by injecting `new RecordedMethods(RecordedMethods::WITH_READS)`.

A response `code` of `400` or greater marks a failed operation, which is ignored; a string of digits such as `"404"` is read the same way. A missing code is treated as success (no failure signal). A code that is present but cannot be read as a status — a non-digit string, a float, a boolean — is treated as a failure too, so only confirmed successes become events.

### Event identity

`Event::$id` is a sha256 hash derived from `method`, `uri`, the timestamp normalized to UTC, and key-sorted `params` — the same operation observed at the same instant is the same event. `result` is excluded on purpose: the same domain operation produces the same event regardless of how its response body was recorded.

Identity is what makes the store a source of truth. Re-extraction reproduces the same ids, and every store treats `append` as idempotent per id, so a retried batch never duplicates facts.

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

Persist extracted events explicitly when an application needs storage. `EventStoreInterface` is a small persistence port (`append`, `appendAll`, `all`), not a runtime hook. Appending is idempotent per `Event::$id` in every implementation, so replaying or retrying a batch is always safe.

The SQL store and the BEAR.Resource bridge are optional features: `ray/media-query` and `bear/resource` are suggested dependencies, required only when you use them.

Use `InMemoryEventStore` for tests and development:

```php
use BEAR\EventSourcing\Store\InMemoryEventStore;

$store = new InMemoryEventStore();
$store->appendAll($events);
```

Use `MediaQueryEventStore` when the EventStore should be backed by SQL through Ray.MediaQuery. MediaQuery stays application-owned; the modules install flat, and EventSourcing modules never hide `AuraSqlModule` or `MediaQuerySqlModule`:

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
        $this->install(new EventSourcingModule());
        $this->install(new MediaQueryEventStoreModule());
    }
}

$store = (new Injector(new AppModule()))->getInstance(EventStoreInterface::class);
$store->appendAll($events);
```

Forgetting `MediaQuerySqlModule` (or `AuraSqlModule`) surfaces as an explicit unbound error at injection time — never as a store that fails on first use.

Apply `sql/event_store/schema.sql` with your application's migration tool before using the SQL store; the bundled SQL uses SQLite dialect (`INSERT OR IGNORE`), so port the two statements when targeting another database. `event_id` is UNIQUE — that constraint is what makes appends idempotent. Timestamps are stored in UTC so the `recorded_at` index sorts in time order. `MediaQueryEventStore` keeps JSON and timestamp database mapping inside the adapter, not on `Event`.

A few operational notes:

- **Already using Ray.MediaQuery?** `#[SqlDir]` is a single binding, so a second `MediaQuerySqlModule` must not point at the package's SQL path — it would clobber your own. Instead, copy `sql/event_store/*.sql` into your application's `sqlDir` and add one more `MediaQuerySqlModule` with `interfaceDir` set to the package's `src/Query` and `sqlDir` set to your own directory. Both installs then bind the same `#[SqlDir]` value, so your queries and the event store coexist:

  ```php
  $this->install(new MediaQuerySqlModule(interfaceDir: $appQueryDir, sqlDir: $appSqlDir));
  $this->install(new MediaQuerySqlModule(
      interfaceDir: $packageDir . '/src/Query',
      sqlDir: $appSqlDir, // event_store_*.sql copied here
  ));
  ```
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

### The application owns the flush

The bridge only writes open/close entries; it never flushes or persists. Inject `SemanticLoggerInterface`, call `$logger->flush()` once per request, and extract (and optionally store) from the returned log. Two operational rules follow:

- The logger accumulates in memory until flushed. In long-running workers (Swoole, FrankenPHP, RoadRunner) it is a singleton that survives requests — flush every request, or observations bleed from one request into the next.
- `flush()` throws Semantic Logger's `NoLogSessionException` when nothing was recorded — for example a request that performed only `GET` reads under the default `RecordedMethods`. Handle it (or check before flushing) in the code that owns the flush.

Note that the bundled `ResourceResponseContext` records `code` and `body_ref` only — it never inlines the body. Events extracted from a bridge log therefore always carry a `null` `result`; the payload lives behind the `body_ref` pointer (see below). Record your own close context with a `body` field when the event itself must carry the result.

For local AI/debug work, use `DevLogModule`. It clears the body directory when the module is constructed, stores rendered bodies as files through `FileBodyStore`, and records `GET` as well as write methods:

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

**Body files** under `bodyDir`, one per recorded operation, numbered in invocation order. The directory is cleared when `DevLogModule` is constructed, so it always reflects the latest run:

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
- Extraction is deterministic: no fallback clock, no type guessing — the same log always yields the same events.
- No automatic persistence during runtime observation; the application owns the flush.
- No `BEAR\Resource\LoggerInterface` decorator.
- Ray.MediaQuery and database installation stay in the application, never hidden inside EventSourcing modules.
- `bear/resource` and `ray/media-query` are suggested dependencies — the core requires only `koriym/semantic-logger` and `ray/di`.
