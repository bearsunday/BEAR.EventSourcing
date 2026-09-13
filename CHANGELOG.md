# Changelog

## Unreleased

### Added

- `skills/bear-observe/`: a Claude Code skill that installs the observation wiring, proves it from the bindings, and reads one request as a tree; absorbs the retired `bear-cache-log` skill from BEAR.QueryRepository
- `skills/bear-observe/`: `setup.php` takes the entry point whose context to observe, both harness scripts fall back to `vendor/autoload.php`, and SKILL.md says which declaration records a dependency and where
- `ParamsFilterInterface` / `FilteredParams` / `SensitiveParamsFilter` / `#[Filtered]`: `SemanticLogInvoker` filters recorded params by default (secure by default, not opt-in), removing credential/transport-shaped keys at every depth. A filter reports per removal whether the request stays `replayable`: a `csrf` substring is transport (removed, `replayable: true` — a replay mints its own CSRF token regardless), while `password`/`token`/`secret` substrings are domain credentials (removed, `replayable: false`). The default deliberately does not match a generic `key` suffix — an `idempotencyKey` is domain input a replay needs, not a secret — so an app-specific `resetKey`/`authKey`-shaped field needs its own `#[Filtered] ParamsFilterInterface`. `ResourceObservationModule`/`DevLogModule` accept and bind it explicitly (a `paramsFilter` constructor parameter, same pattern as `methods`/`bodyStore`/`logger`)
- `ResourceRequestContext::$replayable` / schema (optional, defaults to `true`): a request a filter marked non-replayable is recorded `replayable: false` — still visible in the log for audit purposes, but `SemanticLogExtractor` excludes it from the event stream rather than mint a source-of-truth fact the recorded params cannot reproduce
- `skills/bear-observe/templates/*.php`: `#[Override]` on every generated method that overrides a parent class method or implements an interface method, so a strict downstream Psalm config (`ensureOverrideAttribute=true`) accepts the generated files. The README's own DevModule/AppModule wiring examples were updated to match
- `skills/bear-observe/harness/setup.php`: warns whenever an existing `DevModule` is merged rather than replaced — not only when the file itself mentions `BecomingInterface`, since the shared-logger flush this warns about is just as often wired through a different class the app's `DevModule` installs or overrides

### Note

- Context values that happen to be a whole-number float (`durationMs`, a price) may arrive as either `int` or `float`: koriym/semantic-logger's `ContextFreezer` round-trips every context through its own `json_encode`/`json_decode` at record time without `JSON_PRESERVE_ZERO_FRACTION`, so `0.0` becomes `int(0)` before this package's own code ever runs. Nothing in this package can recover that fraction after the fact — a consumer of a context field that may be a whole-number float must accept `int|float`, not assume `float`

## 0.1.0 - 2026-09-06

### Added

- Event: immutable observed fact with a deterministic sha256 id derived from method, uri, UTC-normalized timestamp, and key-sorted params
- Events / EventsInterface: countable, iterable event collection
- SemanticLogExtractor: extracts events from root (boundary) `resource_request` entries only; nested requests stay in the observation log; requires an absolute ISO-8601 timestamp with an explicit offset, drops failed or uninterpretable response codes
- RecordedMethods: records POST/PUT/PATCH/DELETE by default, GET opt-in via `WITH_READS`; recording and extraction bind under separate keys (`#[Recorded]` / `#[Extracted]`)
- EventStoreInterface with InMemoryEventStore and the Ray.MediaQuery-backed MediaQueryEventStore; appends are idempotent per event id
- EventSourcingModule and MediaQueryEventStoreModule for optional Ray.Di wiring
- ResourceObservationModule: optional BEAR.Resource `InvokerInterface` bridge writing Semantic Logger open/close entries
- FileBodyStore / NullBodyStore / DevLogModule: development `body_ref` storage with a directory ownership guard
- ResourceNodeFormatter: stree formatter for `resource_request` nodes
- SQLite schema and queries for the SQL event store (`event_id` UNIQUE, `INSERT OR IGNORE`)
- EventCollector: flush -> extract -> optional append in one call for request-end handlers
- durationMs on the resource_response close context
- JSON Schemas for observation contexts under docs/schemas, validated in examples/observe
- UnifiedLogModule recipe and test: one shared logger merges the BEAR.QueryRepository cache log into the resource tree
- MediaQueryObservationModule: Ray.MediaQuery's logger seam recorded as media_query leaf events (query id, params, durationMs)
