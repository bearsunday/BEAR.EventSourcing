# BEAR.EventSourcing (WIP)

Event Sourcing for BEAR.Sunday — extracting facts from observations.

## Philosophy

BEAR.Sunday does not provide Event Sourcing as a feature. It emerges naturally by following REST and AOP constraints.

> "A framework is a constraint, not a solution."

## Concept

SemanticLogger records meaningful observations. Event Sourcing extracts state-change facts from them.

```
Observation (SemanticLog) → Extraction → Facts (Events)
```

| Layer | Role | Persistence |
|-------|------|-------------|
| SemanticLog | Complete observation record | Configurable |
| Events | State-change facts only | Permanent |

## Three-Layer Architecture

```
koriym/semantic-logger      Generic structured log primitives (open/close/event tree)
        ▲
bear/semantic-logger        BEAR conventions: ResourceRequest/ResponseContext
                            + SemanticLogger (implements BEAR\Resource\LoggerInterface)
        ▲
bear/event-sourcing         Events::fromSemanticLog() + EventStore (this package)
```

bear/event-sourcing depends on bear/semantic-logger (this repo ships it at `vendor-slogger/`
during development; see "Development layout" below). Neither depends on `ray/aop` — recording
is wired through the existing `BEAR\Resource\LoggerInterface` hook.

## Installation

```bash
composer require bear/event-sourcing
```

## End-to-end demo

A self-contained, runnable demo lives at [`demo/record-and-replay.php`](demo/record-and-replay.php).
It POSTs a couple of resources, flushes the SemanticLog, extracts Events,
persists them in SQLite, then resets the world and replays — verifying the
replay reproduces the originals. Run it after `composer install`:

```bash
php demo/record-and-replay.php
```

Exits 0 when the replay matches the originals. The same flow is exercised
by `tests/EndToEndTest.php` so regressions are caught by `composer test`.

## Usage

### Extract from SemanticLog

```php
use BEAR\EventSourcing\Events;

$logJson = $logger->flush();              // Koriym\SemanticLogger\LogJson
$events = Events::fromSemanticLog($logJson->toArray());
```

`fromSemanticLog()` walks the log tree, picking entries whose `type` is
`resource_request` (paired with a `resource_response` close) and producing one
`Event` per pair.

### Persist & query

```php
$eventStore->appendAll($events);          // single transaction

$eventStore->getEventsByUri('/users/*');
$eventStore->getEventsByAggregateId('orders', '123');
$eventStore->getEventsSince(new DateTimeImmutable('-1 day'));
```

`appendAll` wraps the batch in a transaction; `append` is the single-event
form. Use whichever fits.

### Replay

`$events->replay($handler)` iterates events in chronological order and
hands each to your handler. The library does not prescribe **how** to
re-apply an event — that is intentional, because the right strategy
depends on whether your read model is rebuilt from events or kept in
sync with a separate write side. Three common patterns:

**(a) Re-invoke the resource via the resource client.** Cleanest, exercises
the same code path as the original call. Requires a wired BEAR.Sunday
injector.

```php
$events->replay(function (Event $e) use ($resource): void {
    // $resource is BEAR\Resource\ResourceInterface
    $resource->{strtolower($e->method)}($e->uri, $e->params);
});
```

**(b) Call the resource method directly.** Useful in tests / scripts where
spinning up DI is overkill. The handler maps HTTP method → resource
method name itself (see `demo/record-and-replay.php`).

```php
$events->replay(function (Event $e): void {
    $ro = new Users();
    $ro->{'on' . ucfirst(strtolower($e->method))}(...$e->params);
});
```

**(c) Project into a read model.** The events are the source of truth;
replay rebuilds a separate projection (table, in-memory map, …).

```php
$projection = [];
$events->replay(function (Event $e) use (&$projection): void {
    if ($e->method === 'POST') {
        $projection[$e->result['id']] = $e->result;
    }
    // … etc.
});
```

**Idempotency**: (a) and (b) replay business logic, so they require the
resource's `onPost`/etc. to be idempotent **or** the world (DB,
side-effects) to be reset before replay. (c) rebuilds the projection
from scratch each time and is inherently idempotent.

### Serialize / restore

```php
file_put_contents('events.json', $events->toJson());
Events::fromJson(file_get_contents('events.json'))->replay($handler);
```

### Integration testing

Replay production events for regression testing (assumes idempotency):

```php
Events::fromJson(file_get_contents('production-events.json'))
    ->replay(function (Event $e) use ($resource, $test): void {
        $ro = $resource->{strtolower($e->method)}($e->uri, $e->params);
        $test->assertSame($e->result, $ro->body);
    });
```

## Module

```php
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\SemanticLogger\Module\SemanticLoggerModule;

class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        // ResourceModule (installed by BEAR.Package) already binds
        // BEAR\Resource\LoggerInterface to NullLogger, so SemanticLoggerModule
        // MUST be installed with override() to take effect.
        $this->override(new SemanticLoggerModule());  // binds LoggerInterface -> SemanticLogger
        $this->install(new EventSourcingModule());    // binds EventStoreInterface / EventsInterface
        $this->bind(ExtendedPdo::class)->toInstance(new ExtendedPdo('mysql:...', $user, $pass));
    }
}
```

`SemanticLoggerModule` binds `BEAR\Resource\LoggerInterface → BEAR\SemanticLogger\SemanticLogger`,
so every non-GET resource call is automatically recorded into the SemanticLog tree.

> **Install with `override()`, not `install()`.** `BEAR\Resource`'s
> `ResourceClientModule` binds `LoggerInterface → NullLogger`. A plain
> `install(new SemanticLoggerModule())` leaves that binding in place and
> nothing is recorded. `tests/ResourceClientIntegrationTest.php` verifies
> the `override()` wiring end to end.

## Schema (event_store table)

The schema is dialect-neutral — pick a flavor that fits your DB:

```sql
-- SQLite / minimal portable
CREATE TABLE event_store (
    id         TEXT PRIMARY KEY,
    timestamp  TEXT NOT NULL,
    uri        TEXT NOT NULL,
    method     TEXT NOT NULL,
    params     TEXT,
    result     TEXT
);
```

`EventStore` only appends and reads; events are never updated or deleted. Migration
is your responsibility (the library no longer ships `createTable()`).

## Development layout

During development this repo doubles as a monorepo. The companion package
`bear/semantic-logger` lives at `./vendor-slogger` and is loaded via a composer
path repository declared in this `composer.json`. After `composer install` it
appears in `vendor/bear/semantic-logger` as a symlink. Tests in `tests/` import
its classes directly. Eventually `vendor-slogger/` will be split out into
`bearsunday/BEAR.SemanticLogger`.

Run both test suites:

```bash
composer install                           # root + vendor-slogger via path repo
composer test                              # bear/event-sourcing
cd vendor-slogger && composer install && composer test
```

## Known limitations

- `SCHEMA_URL` constants in `BEAR\SemanticLogger\Resource*Context` point at
  placeholder URLs — actual JSON Schemas will be published separately.
- `EventStoreInterface::getEvents()` returns all events without pagination —
  intended for small stores or batch processing only.
- `composer.json` declares `minimum-stability: dev` because `koriym/semantic-logger`
  has no stable release yet. Tighten when upstream cuts 1.0.

## Design Principles

| Principle | Application |
|-----------|-------------|
| WYSIWYG | Observation is truth |
| Separation | Observation → Fact |
| Single Responsibility | EventStore stores and retrieves |
| Symmetry | `fromJson`/`toJson`, `fromSemanticLog` |
| Transparency | Resources unaware of ES |
| No Global State | DI injection, no order dependency |
