# AGENTS.md

## Project direction

This repository implements event sourcing by extracting immutable event facts from Semantic Logger observations.

Semantic Logger is the observation source. BEAR.Resource runtime observation, when needed, must be an optional `InvokerInterface` bridge that writes Semantic Logger open/close entries. Do not decorate `BEAR\Resource\LoggerInterface` to persist events during request execution.

Recording and observation are separate roles. Events record what a replay re-executes: the boundary (root) requests, state-changing by default. The log observes everything else — nested requests, reads, failures, durations — and is never flattened into the event stream.

Preferred flow:

```text
Semantic Logger observations -> Events -> optional EventStore
```

## Dependency policy

- The core requires only `koriym/semantic-logger` (observations) and `ray/di` (modules).
- `bear/resource` (observation bridge) and `ray/media-query` (SQL EventStore) are optional features: suggested dependencies, installed in require-dev for tests.
- Do not copy Semantic Logger code into this repository.
- If Semantic Logger behavior must be changed temporarily, isolate it with `cweagans/composer-patches` and files under `patches/`.
- Upstream Semantic Logger changes later as a separate PR.

## Concurrency

The observation stack assumes one request per process (PHP-FPM, mod_php, CLI). Recording is not supported on a host where one process serves multiple requests (RoadRunner, a Swoole coroutine or worker, FrankenPHP worker mode, ReactPHP, Amp, a resident CLI consumer): BEAR.QueryRepository ships no product `SessionStoreInterface` implementation keyed by request context (only `ProcessSession`, one session per process) and no request-scoped `LogSinkInterface` (only `ShutdownFlush`, which arms once per process and never re-checks). Adopting `SafeSemanticLogger` as the default binding is blocked by the dependency policy above until such implementations exist, since it lives in `bear/query-repository`, not `koriym/semantic-logger`.

`MediaQueryObservationModule` binds `SemanticLogMediaQueryLogger` as `Scope::SINGLETON`, but the class holds per-call mutable state (`$start`, `$lines`). This is a defect independent of session-store readiness: any host that reuses the instance across overlapping or interleaved calls corrupts it. Fixing it means making the adapter's state per-request/context-keyed, and is worth doing regardless of concurrent-host support work.

See issue #23 for the full investigation.

## Implementation discipline

- Keep each commit focused on one concept.
- Prefer README and tests before expanding implementation.
- Keep public API minimal until validated by usage.
- Do not add application-specific domain code to this repository.

## Scope

Core components:

- `Event`
- `EventsInterface` / `Events`
- `SemanticLogExtractorInterface` / `SemanticLogExtractor`
- `RecordedMethods`
- `EventCollector` for request-boundary flush -> extract -> optional append
- Semantic Logger log fixtures and extractor tests
- `EventStoreInterface`, in-memory implementation, and Ray.MediaQuery SQL implementation
- `EventSourcingModule` and `MediaQueryEventStoreModule` for optional ES bindings
- Optional BEAR.Resource observation bridge using `InvokerInterface`
- Optional Ray.MediaQuery observation adapter using `MediaQueryLoggerInterface`
- Development `FileBodyStore` / `DevLogModule` for local body refs

Keep out of this package:

- BEAR.Resource logger decorator
- Runtime auto-persistence
- Inline BEAR.Resource response-body persistence policy; use `BodyStoreInterface` and `body_ref`
- Application-specific BEAR module wiring
- Hidden installation of application-owned MediaQuery or database modules from ES modules
- Application-specific domain code
- Bundled subpackages
