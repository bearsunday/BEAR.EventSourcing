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
foreach ($events as $event) {
    $eventStore->append($event);
}

$eventStore->getEventsByUri('/users/*');
$eventStore->getEventsByAggregateId('orders', '123');
$eventStore->getEventsSince(new DateTimeImmutable('-1 day'));
```

### Replay

```php
$events->replay(function (Event $e) use ($resource) {
    $resource->{strtolower($e->method)}->withQuery($e->params)->eager()->request($e->uri);
});
```

### Serialize / restore

```php
file_put_contents('events.json', $events->toJson());
Events::fromJson(file_get_contents('events.json'))->replay($handler);
```

### Integration testing

Replay production events for regression testing (assumes idempotency):

```php
Events::fromJson(file_get_contents('production-events.json'))
    ->replay(function (Event $e) use ($resource, $test) {
        $ro = $resource->{strtolower($e->method)}->withQuery($e->params)->eager()->request($e->uri);
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
        $this->install(new SemanticLoggerModule());   // binds BEAR\Resource\LoggerInterface
        $this->install(new EventSourcingModule());    // binds EventStoreInterface / EventsInterface
        $this->bind(ExtendedPdo::class)->toInstance(new ExtendedPdo('mysql:...', $user, $pass));
    }
}
```

`SemanticLoggerModule` binds `BEAR\Resource\LoggerInterface → BEAR\SemanticLogger\SemanticLogger`,
so every non-GET resource call is automatically recorded into the SemanticLog tree.

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
- POST/PUT request bodies are recorded via `$ro->uri->query` only. Capturing
  arbitrary request bodies requires a future hook in BEAR.Resource.
- `EventStoreInterface::getEvents()` returns all events without pagination —
  intended for small stores or batch processing only.

## Design Principles

| Principle | Application |
|-----------|-------------|
| WYSIWYG | Observation is truth |
| Separation | Observation → Fact |
| Single Responsibility | EventStore stores and retrieves |
| Symmetry | `fromJson`/`toJson`, `fromSemanticLog` |
| Transparency | Resources unaware of ES |
| No Global State | DI injection, no order dependency |
