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
- Hide `AuraSqlModule` or `MediaQuerySqlModule` inside EventSourcing modules.

## Module placement (BEAR.Sunday contexts)

Three independent layers map onto context modules. Keep `EventSourcingModule` out of `AppModule` so contexts that never touch event sourcing stay clean; install it only where it is used.

- **Observe**: the bridge decorates the `InvokerInterface` binding the app already has — how depends on where you wire it:
  - Standalone injector (tests, scripts): pass the resource module as `module:` — `new DevLogModule($dir, module: new ResourceModule(...))`.
  - BEAR.Sunday context module (`dev-` prefix, e.g. `dev-hal-app`): the context module inherits the whole inner chain, so `rename(InvokerInterface::class, ...)` and bind `SemanticLogInvoker` over it in place (see README "Wiring inside a BEAR.Sunday context"). NEVER `override(new DevLogModule(module: new PackageModule()))` — the second `PackageModule` registers the framework pointcuts again and every interceptor runs twice; the cache log shows it as a `get` scope nested in itself.
- **Extract**: `EventSourcingModule` binds `SemanticLogExtractorInterface`. Recording and extraction are separate policy keys (`#[Recorded]` / `#[Extracted]`), so dev GET recording never widens the writes-only extraction default.
- **Persist**: install `MediaQueryEventStoreModule` alongside only when replay/persistence is needed (with application-owned `AuraSqlModule` + `MediaQuerySqlModule`).
- **Unify with the QueryRepository cache log**: alias the `#[CacheLog]` logger to the same instance. Plain injector: `toInstance()`. Compiled injector (Sunday context): a provider returning the injected unannotated logger — `toInstance` would serialize the instance and split the tree per process.

Typical split — `DevModule`: rename+decorate + `EventSourcingModule()` (observe, no store). `ProdModule`: the same decoration with the default `RecordedMethods` + `EventSourcingModule()` + `MediaQueryEventStoreModule` (observe + persist). The application owns the flush once per request at the request end (`public/index.php`, or the worker runtime's request-end event via `EventCollector`).

## Development observation output

`DevLogModule` produces two artifacts for inspection:

- **Body files** under `bodyDir`, numbered in invocation order (`000001.json`, `000002.json`, …), each holding the rendered body `(string) $ro`. The directory is cleared when `DevLogModule` is constructed.
- **The Semantic Logger log**, in memory until `SemanticLoggerInterface::flush()`. It is a nested open/close **tree** (`LogJson`): child operations sit under their parent's `open`, each node has a request `context` (`uri`/`method`/`params`/`timestamp`) and a `close` whose `context` carries `code` and a `body_ref` to the matching body file. `GET` is recorded too (`WITH_READS`). This package never writes the log to disk.

Read the log as a tree — far fewer tokens than raw JSON, so prefer it for both human and AI inspection. `Resource\Stree\ResourceNodeFormatter` renders each node as one resource operation (intent in, result out):

```text
request="POST app://self/orders?order_id=O-1000"
├── request="PUT app://self/inventory/SKU-1?sku=SKU-1&quantity=1"
│   ├── media_query name=inventory_reserve durationMs=0.42 [event]
│   └── code=200 body_ref=file://var/es/bodies/000001.json
└── code=201 body_ref=file://var/es/bodies/000002.json
```

Every node follows one rule: intent inline, heavy detail behind a `*_ref` pointer, so an AI can follow it for the full detail. This bridge emits `resource_request` nodes with `body_ref`; `MediaQueryObservationModule` (optional, Ray.MediaQuery's logger seam) adds `media_query` leaf events — query id, params, wall time; successful invocations only (a multi-statement `.sql` is one event), `getCount()` unobserved, paginated queries measure only lazy-wrapper construction, and appends after the flush (the EventCollector path) open the next session as top-level events. Render with `Koriym\SemanticLogger\Stree\TreeRenderer` + a `FormatterRegistry` that registers `ResourceNodeFormatter` for `resource_request`; `examples/tree.php` builds a `DevLogModule`-style log and renders it, with `examples/semantic-tree.txt` as its output. The bundled `vendor/bin/stree <log.json>` works but shows the generic form (type label + raw `timestamp`) because the CLI loads no custom formatters.

## Examples

Reach for the bundled examples before inventing new integration code: `extract.php`, `store.php`, `replay.php` cover extraction, storage, and replay; `tree.php` renders the log as a resource tree. They share the `examples/semantic-log.json` fixture, built through `SemanticLogger::flush()`.
