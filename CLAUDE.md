# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

Composer scripts (PHP 8.1+):

- `composer test` — run the PHPUnit suite
- `composer stan` — PHPStan static analysis
- `composer cs` / `composer cs-fix` — PHP_CodeSniffer check / auto-fix
- Run a single test: `vendor/bin/phpunit --filter testCreate tests/EventSourcing/EventTest.php`

`vendor/` is gitignored — run `composer install` before any of the above.

## Architecture

This is an Event Sourcing library for BEAR.Sunday. The core idea: BEAR.Sunday does not provide Event Sourcing as a feature — it emerges from REST + AOP constraints. Events are state-change facts extracted from resource method invocations.

### Data flow

```
ResourceObject method call (non-GET)
  → EventSourcingInterceptor (Ray.Aop)
  → Event::create(uri, method, params, result)
  → EventStoreInterface::append()
```

Reading back:

```
EventStore::getEvents*()  →  Events (collection)  →  replay($handler) / toJson()
```

`Events::fromJson()` / `toJson()` are symmetric — events serialized in production can be replayed in tests for regression / bug reproduction (assumes idempotency).

### Module wiring (`src/Module/EventSourcingModule.php`)

`EventSourcingModule` binds `EventStoreInterface → EventStore` and `EventsInterface → Events`, then binds `EventSourcingInterceptor` to any `ResourceObject` method starting with `onPost`, `onPut`, or `onDelete`. `onGet` is deliberately excluded — read-only methods produce no events, which also avoids cache interceptor conflicts.

The interceptor itself (`src/Interceptor/EventSourcingInterceptor.php`) re-checks for `onGet` as a safety net and derives the HTTP verb from the method name prefix (`onPost` → `POST`, etc.).

### Storage

`EventStore` uses `Aura\Sql\ExtendedPdo` against a single `event_store` table (`createTable()` provisions it). URI-pattern queries convert glob (`*`, `?`) to SQL `LIKE` (`%`, `_`). `getEventsByAggregateId($type, $id)` is a convention-based helper that matches URIs like `/{type}/{id}%`.

### Namespace quirk

PSR-4 maps `BEAR\EventSourcing\` → `src/`, but source files live under `src/EventSourcing/` and `src/Interceptor/`, so the actual namespaces are **`BEAR\EventSourcing\EventSourcing`** (double segment) and `BEAR\EventSourcing\Interceptor`. The `autoload-dev` block maps the same `BEAR\EventSourcing\` prefix to `tests/`, so test classes mirror the doubled namespace (e.g. `BEAR\EventSourcing\EventSourcing\EventTest`).

### Design invariants

- `Event` is immutable (`final`, `readonly` properties, private constructor — construct only via `Event::create()` or `Event::fromArray()`).
- `Events` collection methods (`add`, `filterByUri`, `filterByMethod`, `since`) return new instances — never mutate.
- `EventStore` only appends and reads; events are never updated or deleted.
- Resources stay unaware of Event Sourcing — recording is purely an AOP concern.

## Git workflow

Development branch for this work: `claude/init-project-setup-49Jtg` (per task instructions). Push with `git push -u origin <branch>` and open a draft PR after pushing.
