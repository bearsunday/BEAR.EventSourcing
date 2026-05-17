# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

Composer scripts (PHP 8.1+):

- `composer test` — run the PHPUnit suite
- `composer stan` — PHPStan static analysis (level 0 by default)
- `composer cs` / `composer cs-fix` — PHP_CodeSniffer (PSR-12) check / auto-fix
- Single test: `vendor/bin/phpunit --filter testAppendAndGetEventsRoundTrip tests/EventStoreTest.php`

`vendor/` is gitignored — run `composer install` first.

For the companion package, repeat inside `vendor-slogger/`:

```bash
cd vendor-slogger && composer install && composer test
```

## Architecture

Event Sourcing library for BEAR.Sunday, designed as a three-layer cake:

```
koriym/semantic-logger      Generic tree-shaped structured log (open/close/event)
        ▲
bear/semantic-logger        Lives in this repo at vendor-slogger/. Bridges BEAR.Resource
                            via LoggerInterface → emits ResourceRequest/ResponseContext
                            pairs into the SemanticLog tree.
        ▲
bear/event-sourcing         This package. Events::fromSemanticLog(array) walks the tree
                            for resource_request/resource_response pairs and produces
                            Event objects; EventStore persists/queries them.
```

### Why no AOP interceptor

Recording is wired through BEAR.Resource's existing `LoggerInterface` (a per-resource
post-call hook used by `NullLogger` / `ProdLogger` / etc.). `bear/semantic-logger` ships
`SemanticLogger implements LoggerInterface` and is bound via `SemanticLoggerModule`.
This means `bear/event-sourcing` itself does not depend on `bear/resource` or `ray/aop`.

### Reading back

```
EventStore::getEvents*()  →  Events  →  replay($handler) | toJson()
```

`Events::fromJson()` / `toJson()` are symmetric. Production events can be replayed in
tests for regression / bug reproduction (assumes idempotency).

### Storage

`EventStore` uses `Aura\Sql\ExtendedPdo` against a single user-supplied table
(default `event_store`). It uses only standard SQL (`INSERT`, `SELECT … WHERE … LIKE …
ESCAPE '\\'`) so SQLite, MySQL, and Postgres all work. Migration is the caller's
responsibility (`createTable()` is intentionally absent).

URI glob lookups (`*`, `?`) are converted to SQL LIKE with `%`/`_` escaping; user
input cannot inject wildcards.

### Monorepo-style development layout

`vendor-slogger/` is a self-contained package (`bear/semantic-logger`) — own
`composer.json`, `src/`, `tests/`. The root `composer.json` declares a path
repository `./vendor-slogger` so `composer install` symlinks it into
`vendor/bear/semantic-logger`. Eventually `vendor-slogger/` will split out
into `bearsunday/BEAR.SemanticLogger`.

### Design invariants

- `Event` is immutable (`final`, `readonly` properties, private constructor — construct
  only via `Event::create()` or `Event::fromArray()`). `fromArray` validates required keys.
- Timestamps round-trip as `Y-m-d\TH:i:s.uP` (microseconds + offset) — lossless across
  `fromArray`/`toArray`.
- `Events` collection methods (`add`, `filterByUri`, `filterByMethod`, `since`) return new
  instances — never mutate.
- `EventStore` only appends and reads; events are never updated or deleted.
- Resources stay unaware of Event Sourcing — recording is purely a `LoggerInterface` concern.

## Git workflow

Development branch: `claude/init-project-setup-49Jtg`. Push with `git push -u origin <branch>`
and open a draft PR after pushing.
