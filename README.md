# BEAR.EventSourcing

Make your application's state changes a **replayable source of truth** — fold the events back to reconstruct state at any point in time.

Every event is a resource operation: a `method` on a `uri`, like `POST app://self/users`. That resource shape keeps replay straightforward, and it lets the same event stream double as an audit history of the recorded state changes: which write, when, to which resource.

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

An `Event` is a successful resource operation observed at the root of a Semantic Logger log: a boundary request, an open/close pair of type `resource_request` with no parent. State-changing methods qualify by default; a root `GET` qualifies only under the opt-in `WITH_READS` policy below. It carries only the facts it observed:

| Field | Source |
| --- | --- |
| `id` | derived from the observed facts (see below) |
| `uri` | request `uri` |
| `method` | request `method` (upper-cased) |
| `params` | request `params`, falling back to `query` |
| `timestamp` | request `timestamp` — absolute ISO-8601 with an explicit offset; an entry without one is not extracted |
| `result` | `close.context.body` when present |

Extraction is deterministic: it never invents facts. There is no fallback clock for a missing `timestamp`, and only root entries of type `resource_request` qualify — another subsystem's open/close pair that happens to carry `method` and `uri` fields is never misread as a state change, and a request nested inside another request is never extracted (see [Recording and observation](#recording-and-observation)). Extracting the same log twice yields the same events.

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

## Recording and observation

The log and the event stream answer different questions. The event stream records what can be **reproduced**: the boundary write requests (root reads only when opted in), the input a replay re-executes. The log observes what **happened**: every node — reads, failures, nested requests, `durationMs` — for transparency, debugging, and checking a replay against the original run.

Extraction therefore takes root entries only. A `POST app://self/orders` whose handler issues `PUT app://self/inventory/SKU-1` yields one event, the POST. Replaying it re-executes the handler, which issues the PUT again; had the PUT been recorded as well, replay would apply it twice. This is the same boundary MySQL statement-based replication draws when it logs a statement but not the writes its triggers perform. The nested PUT is still in the log, as observation.

Replay by re-execution rests on two conditions the package assumes but does not enforce, the way MySQL flags unsafe statements rather than refusing them:

- **Handlers are deterministic.** A replay reproduces the original run only when the handler's outcome depends solely on its recorded `params`. A clock, a random value, or an external read the handler consults on its own makes the request unsafe to replay; pass such values in as params so they are recorded.
- **A request is a transaction boundary.** All of a request's writes commit or none of them do. Without this, a handler whose nested `PUT` committed before the root request failed with a `500` leaves a state change that no event records — the root failed, so nothing was extracted.

Two natural extensions are not implemented: verifying determinism by diffing the observation tree a replay produces against the original, and appending events inside the request's own transaction (an outbox). Runtime auto-persistence remains out of scope.

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

$injector = new Injector(new AppModule());
$store = $injector->getInstance(EventStoreInterface::class);
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
- **`appendAll` is not atomic.** It appends events one row at a time with no surrounding transaction, so a mid-way failure leaves earlier rows written. The database is application-owned, so wrap a batch in your own transaction when you need all-or-nothing — this is also markedly faster, since one commit replaces one fsync per event. Resolve the PDO from the same injector as `$store` so the transaction wraps the connection MediaQuery writes through; a separate `new PDO(...)` opens its own connection, leaving the transaction guarding nothing while every row still commits:

  ```php
  $pdo = $injector->getInstance(\Aura\Sql\ExtendedPdoInterface::class);

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
- `flush()` returns an empty log when nothing was recorded — for example a request that performed only `GET` reads under the default `RecordedMethods`. Extracting an empty log yields empty events, so the flush owner has no exception to handle.

`EventCollector` packages flush -> extract -> optional append into one call for the handler that owns the request boundary. In worker runtimes, call it from the runtime's own request-end event (a Swoole request close, a RoadRunner worker-loop iteration) — no process-shutdown hook is involved:

```php
use BEAR\EventSourcing\EventCollector;

$collect = new EventCollector($logger, $extractor, $store); // store is optional

$events = $collect(); // once per request, at the boundary
```

The collector is the persistence boundary: it consumes the session and returns only the events — the durable facts. A development request end that also wants the tree owns the flush directly and extracts from the same log:

```php
$log = $logger->flush();                          // the tree, for inspection
file_put_contents($logFile, json_encode($log));
$events = $extractor->extract($log);              // the facts
```

Note that the bundled `ResourceResponseContext` records `code`, `body_ref`, and `durationMs` — it never inlines the body. Events extracted from a bridge log therefore always carry a `null` `result`; the payload lives behind the `body_ref` pointer (see below). Record your own close context with a `body` field when the event itself must carry the result.

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

### Wiring inside a BEAR.Sunday context

Passing `module:` is for a standalone injector, where the bridge's wrapped module is the only provider of `InvokerInterface`. A BEAR.Sunday context module (a `dev-` prefix such as `dev-hal-app`) inherits the whole inner chain instead, so it renames the chain's own binding and decorates it in place:

```php
final class DevModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $bodyDir = $this->appMeta->logDir . '/es-bodies';
        FileBodyStore::clearDirectory($bodyDir);

        $this->rename(InvokerInterface::class, 'original_invoker');
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => 'original_invoker',
                'recordedMethods' => Recorded::class,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)->toInstance(new FileBodyStore($bodyDir));
        $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
        $this->install(new EventSourcingModule());
    }
}
```

Do **not** re-provide the package bindings there — `override(new DevLogModule(module: new PackageModule()))` registers the framework pointcuts a second time and every interceptor runs twice. The cache log makes the double execution visible as a `get` scope nested in itself; without a log it is invisible.

Recording and extraction stay separate policies (`#[Recorded]` / `#[Extracted]`): the `WITH_READS` recording above does not widen the extractor's writes-only default.

A `BodyStoreInterface` records a `body_ref` in the close context:

```json
{"code": 200, "body_ref": "file:///path/to/var/es/bodies/000001.json", "durationMs": 0.42}
```

`body_ref` is a reference to a stored rendered body. It stays in the Semantic Log for inspection and is **not** extracted into `Event::$result` — the event's `result` comes from `close.context.body`. A bridge log that records only `body_ref` therefore yields an event with a `null` result; the payload lives in the externalized body, not in the event. The same domain operation produces the same event regardless of which `BodyStoreInterface` the bridge uses.

`durationMs` is the wall time of the invocation, children included. It is observation data: it stays in the close context and is never part of event identity, for the same reason `result` is excluded — the same domain operation is the same event regardless of how long it took. The log answers *which* request was slow and *where* in the tree; *why* remains the profiler's job.

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
│   ├── media_query name=inventory_reserve durationMs=0.42 [event]
│   └── code=200 body_ref=file://var/es/bodies/000001.json
└── code=201 body_ref=file://var/es/bodies/000002.json
```

The request line is the intent (`method` on a `uri` with its params as a query string); the `└──` close line is the result (`code` plus the `body_ref` pointer). Child operations nest under their parent — a resource calling a resource, and a resource embedding a non-resource operation such as a media query, recorded as a leaf event with its intent and wall time (see the Ray.MediaQuery observation below). Every node follows one rule: the intent is inline, the heavy detail sits behind a `*_ref` pointer. The resource shape keeps the tree normalized, and no timestamp noise leaks in. When debugging, follow a node's `*_ref` to its file for the full detail.

Render it with `TreeRenderer` and a `FormatterRegistry` that registers `ResourceNodeFormatter` for the `resource_request` type — `examples/tree.php` builds a `DevLogModule`-style log (`body_ref` pointers) and renders it, and `examples/semantic-tree.txt` is its output. The bundled `vendor/bin/stree dev-log.json` works too, but renders the generic form (type label plus a raw `timestamp`) since the CLI does not load custom formatters. This package never writes the log to disk itself; `examples/semantic-log.json` is the raw `LogJson` of the extraction examples.

### Ray.MediaQuery observation (optional)

Ray.MediaQuery exposes a logger seam (`MediaQueryLoggerInterface`) that brackets each query execution. `MediaQueryObservationModule` routes it into the semantic log as one `media_query` leaf event per executed query — the query id, its converted parameters, and wall time measured in the adapter — nested under whichever scope is open:

```php
use BEAR\EventSourcing\Module\MediaQueryObservationModule;

$this->install(new MediaQueryObservationModule()); // before the MediaQuery modules
```

The module installs flat: only the logger binding — the adapter needs the unqualified `SemanticLoggerInterface` binding, the same instance the resource bridge and the flush owner use. Boundaries to know:

- A failed query throws before the seam fires, so only successful queries are recorded. A `.sql` file may hold several statements: one event covers the whole invocation, and a failure anywhere in the batch suppresses it.
- `getCount()` runs outside the seam and stays unobserved. A paginated query (`#[Pager]`) passes through the seam only while its lazy wrapper is constructed — the event's near-zero duration is wrapper construction, and the count/page SQL that runs at iteration time is unobserved.
- Result rows are not available through the seam; the `media_query` schema reserves an optional `rows_ref` for a richer upstream logger contract.
- The observer sees every query the seam fires for, including the event store's own appends. Where they land depends on when they run: inside an open scope they nest there; after the flush (the `EventCollector` path) they open the next session as top-level events.
- The singleton adapter keeps one start marker, so overlapping queries in a coroutine runtime would corrupt durations. Observe per-request runtimes, like the rest of the observation stack.

### The log is a verifiable contract

Every context names its JSON Schema (`schemaUrl`). The canonical schema files live in `docs/schemas/` and are published at `https://bearsunday.github.io/BEAR.EventSourcing/schemas/`. Validate a flushed log offline with Semantic Logger's validator:

```php
use Koriym\SemanticLogger\SemanticLogValidator;

(new SemanticLogValidator())->validate($logFile, $projectDir . '/docs/schemas');
```

`examples/observe/observe.php` ends by validating its own run, so a contract break fails the demo.

## One tree with BEAR.QueryRepository (optional)

BEAR.QueryRepository's semantic cache log records through a `#[CacheLog]`-qualified `SemanticLoggerInterface`. Bind the same instance under both keys and the two observations become one tree: cache scopes nest inside the `resource_request` scope that caused them.

```php
use BEAR\RepositoryModule\Annotation\CacheLog;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;

$logger = new SemanticLogger();

$this->install(new DevLogModule($bodyDir, logger: $logger, module: $appModule));
$this->bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)->toInstance($logger);
```

Share the instance with `toInstance()`: two separate `to(...)->in(Scope::SINGLETON)` bindings would create one singleton per binding key and split the tree in two. Extraction stays safe in the merged tree — only `resource_request` entries become events, so cache scopes are never misread as state changes. `tests/UnifiedLogTest.php` proves both properties.

Under a compiled injector (a BEAR.Sunday context), alias with a provider instead of `toInstance` — the compiler would serialize the logger instance and each process would get its own copy, splitting the tree again:

```php
final class CacheLogProvider implements ProviderInterface
{
    public function __construct(private readonly SemanticLoggerInterface $logger)
    {
    }

    public function get(): SemanticLoggerInterface
    {
        return $this->logger;
    }
}

$this->bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)
    ->toProvider(CacheLogProvider::class)->in(Scope::SINGLETON);
```

## Boundaries

- Semantic Logger is the observation source; EventStore is an optional destination.
- Extraction is deterministic: no fallback clock, no type guessing — the same log always yields the same events.
- Extraction records boundary requests only; a nested request is observation, never an event.
- No automatic persistence during runtime observation; the application owns the flush.
- No `BEAR\Resource\LoggerInterface` decorator.
- Ray.MediaQuery and database installation stay in the application, never hidden inside EventSourcing modules.
- `bear/resource` and `ray/media-query` are suggested dependencies — the core requires only `koriym/semantic-logger` and `ray/di`.
