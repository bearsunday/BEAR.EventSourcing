---
name: event-sourcing
description: Work with BEAR.EventSourcing integrations, examples, or reviews. Use this skill whenever editing this repository's event observation or extraction flow, extracting events from Koriym Semantic Logger LogJson, iterating EventsInterface, filtering/replaying events, wiring EventStoreInterface, reviewing EventStore or BEAR.Resource observation module boundaries, or deciding how an AI agent should use this package.
---

# BEAR.EventSourcing

Use Semantic Logger as the observation source and EventStore as an optional destination.

```text
BEAR.Resource Invoker -> Koriym\SemanticLogger\LogJson -> SemanticLogExtractorInterface -> EventsInterface -> optional EventStoreInterface
```

## Workflow

1. Read `README.md` for the public API and worked code examples, and `AGENTS.md` for project direction and dependency policy.
2. Use `SemanticLogExtractorInterface::extract(LogJson $semanticLog)` to create events.
3. Iterate events with `foreach`, `EventsInterface::getIterator()`, or SPL iterators.
4. Use `RecordedMethods::WITH_READS` only for development-time GET tracing.
5. Use `ResourceObservationModule` only when BEAR.Resource execution should emit Semantic Logger open/close entries.
6. Persist with `EventStoreInterface` only when the application explicitly chooses storage.

## Boundaries

Do:

- Keep `Event`, `EventsInterface`, and `Events` minimal.
- Use `CallbackFilterIterator` or other iterators for method, URI, parameter, and timestamp selection.
- Use `BodyStoreInterface` when a response body must be stored out of line; keep only `body_ref` in the Semantic Log.
- Use `DevLogModule` for local AI/debug runs that should clear a body directory and store `FileBodyStore` refs.
- Keep Ray.MediaQuery and database installation in the application.
- Use `InMemoryEventStore` for examples and tests.

Do not:

- Add a BEAR.Resource logger decorator.
- Persist automatically during runtime observation.
- Inline large response bodies in BEAR.Resource observation logs.
- Promote a `*_ref` storage pointer (e.g. `body_ref`) into `Event::result`. `Event::result` reflects `close.context.body`; keys ending in `_ref` are storage pointers that stay in the Semantic Log for inspection only, never extracted into the event.
- Make MCP, CLI, or skills part of the core runtime contract.
- Hide `AuraSqlModule` or `MediaQuerySqlModule` inside EventSourcing modules.

## Module placement (BEAR.Sunday contexts)

Three independent layers map onto context modules. Keep `EventSourcingModule` out of `AppModule` so contexts that never touch event sourcing stay clean; install it only where it is used.

- **Observe**: `DevLogModule` (dev) or `ResourceObservationModule` (prod). Both need the BEAR.Resource module passed as `module:` so the bridge can `rename(InvokerInterface)`.
- **Extract**: `EventSourcingModule` binds `SemanticLogExtractorInterface`.
- **Persist**: pass a store (e.g. `MediaQueryEventStoreModule`) to `EventSourcingModule(store: ...)` only when replay/persistence is needed.

Typical split — `DevModule`: `DevLogModule` + `EventSourcingModule()` (observe, no store). `ProdModule`: `ResourceObservationModule` + `EventSourcingModule(store: new MediaQueryEventStoreModule())` (observe + persist).

## Development observation output

`DevLogModule` produces two artifacts for inspection:

- **Body files** under `bodyDir`, numbered in invocation order (`000001.json`, `000002.json`, …), each holding the rendered body `(string) $ro`. The directory is cleared at injector creation.
- **The Semantic Logger log**, in memory until `SemanticLoggerInterface::flush()`. It is a nested open/close **tree** (`LogJson`): child operations sit under their parent's `open`, each node has a request `context` (`uri`/`method`/`params`/`timestamp`) and a `close` whose `context` carries `code` and a `body_ref` to the matching body file. `GET` is recorded too (`WITH_READS`). This package never writes the log to disk.

Read the log as a tree with `stree` (bundled with `koriym/semantic-logger`) — far fewer tokens than raw JSON, so prefer it for both human and AI inspection:

```text
resource_request uri=app://self/orders method=POST params=[order_id] timestamp=...
├── resource_request uri=app://self/inventory/SKU-1 method=PUT params=[sku] timestamp=...
│   └── code=200 body_ref=file://.../000001.json
└── code=201 body_ref=file://.../000002.json
```

Run `vendor/bin/stree <log.json>` (`--full` to expand context keys) or use `Koriym\SemanticLogger\Stree\TreeRenderer`. `examples/semantic-log.json` is the raw `LogJson`; `examples/semantic-log.tree` is its `stree` rendering (committed as a worked example).

## Examples

Use the bundled examples before inventing new integration code:

```bash
php examples/extract.php
php examples/store.php
php examples/replay.php
```

`examples/semantic-log.json` is a public Semantic Logger tree fixture for inspection. The runnable examples build a `LogJson` through `SemanticLogger::flush()` and then extract events.

## Validation

For library changes, run:

```bash
composer tests
composer psalm
composer require-checker
composer validate --strict
```
