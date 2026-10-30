# Changelog

## 0.3.0 - 2026-10-30

### Migration

- **Delete the old body directory once when upgrading from 0.2.0.** 0.2.0 wrote numbered files straight into `bodyDir` and marked `bodyDir` itself as owned. Those files are not generations, so retention never removes them, and the leftover marker lets one `FileBodyStore::clearDirectory($bodyDir)` succeed and wipe every generation under it before the call starts throwing `UnownedDirectoryException`.

### Added

- `FileBodyStore::MARKER` is public (was a private constant every consumer had to re-state) and `FileBodyStore` takes a `keep` constructor parameter (default 5): the store now owns its own generation retention instead of a host reimplementing it
- `UnownedDirectoryException extends BodyStoreException`: `FileBodyStore::clearDirectory()` throws it specifically when a directory lacks the ownership marker, distinguishable from the general I/O-failure `BodyStoreException` a caller may want to let propagate

### Fixed

- `FileBodyStore` no longer creates its directory in the constructor. A host wires `BodyStoreInterface` while building the injector, before routing knows the request method; `SemanticLogInvoker` stores nothing for a method outside `RecordedMethods::WITH_READS`, so the directory used to exist even for a request that recorded nothing. Any per-request retention scheme built on that directory advanced on every injector build but only sometimes gained a log to reference it, so count-based pruning drifted out of sync with what was actually retained until a kept log's `body_ref` pointed at an already-pruned directory. `FileBodyStore` now creates nothing until the first body is stored, names that store's directory uniquely (so one root can be shared across sessions or processes without their sequences colliding), and prunes sibling generations it marked itself down to `keep`. Pruning is best effort: a generation that cannot be removed (permissions, another process removing it first) raises an `E_USER_WARNING` and is skipped instead of failing the write, and a symlinked entry under the root is never treated as a generation. The `bear-observe` DevModule template passes the same `keep` to `FileBodyStore` and `DevQueryRepositoryLogModule`, so no retained cache log points at a pruned body. `DevLogModule` and the `skills/bear-observe/templates/DevModule.php` template stop clearing the body directory at construction/`configure()` for the same reason (#28)

### Changed

- **BREAKING — `FileBodyStore::clearDirectory()` no longer works against a `FileBodyStore`'s own root directory.** `createGeneration()` never marks the root itself — only the generation subdirectories it creates under it — so a host that paired `new FileBodyStore($dir)` with a `clearDirectory($dir)` teardown now gets `UnownedDirectoryException` on that call. Retention (`keep`) replaces the need for that teardown; a host that still wants to wipe everything under a root should recursively delete the directory itself rather than call `clearDirectory()` on it

## 0.2.0 - 2026-10-28

### Migration

- **If you use `MediaQueryEventStore`, do this before upgrading, or a non-replayable event is silently stored as replayable.** `event_store.replayable` is a new, required column. Run `ALTER TABLE event_store ADD COLUMN replayable INTEGER NOT NULL DEFAULT 1;` on an existing table, and re-copy both `sql/event_store/event_store_list.sql` and `sql/event_store/event_store_append.sql` if your app owns a copy of them. A stale `event_store_list.sql` fails loudly (`EventStoreException`); a stale `event_store_append.sql` does not — the statement omits the `replayable` column entirely, so SQLite fills it with its `DEFAULT 1`, and the verdict a filter computed is lost with no error, no warning (`INSERT OR IGNORE` is not the cause here: it only silences a true constraint violation, such as an explicit `NULL`, not an omitted column). `InMemoryEventStore` is unaffected.

### Added

- `skills/bear-observe/`: a Claude Code skill that installs the observation wiring, proves it from the bindings, and reads one request as a tree; absorbs the retired `bear-cache-log` skill from BEAR.QueryRepository
- `skills/bear-observe/`: `setup.php` takes the entry point whose context to observe, both harness scripts fall back to `vendor/autoload.php`, and SKILL.md says which declaration records a dependency and where
- `skills/bear-observe/`: `setup.php` accepts `--context=<literal>` to name the context literal directly when an entry point holds more than one, and notes (without failing) when the value has no matching quoted literal in the entry point or when `{namespace}\Bootstrap` is missing at `src/Bootstrap.php`
- `ParamsFilterInterface` / `FilteredParams` / `SensitiveParamsFilter` / `#[Filtered]`: `SemanticLogInvoker` filters recorded params by default (secure by default, not opt-in), replacing the value of every credential/transport-shaped key at every depth with `[FILTERED]` (`SensitiveParamsFilter::FILTERED`) — the key stays, as with Rails' `filter_parameters` and Sentry's scrubber, so the log shows a credential was sent. Object values are walked on the JSON view the log records; a value that cannot be encoded — a resource open or closed, a non-finite float, a byte string that is not valid UTF-8 — is withheld whole, and nesting past 128 levels is cut, an over-deep array keeping the levels above the bound. A filter reports one `replayable` verdict for the whole result, decided by what it withheld: a `csrf` substring is transport (filtered, `replayable: true` — a replay mints its own CSRF token regardless; a credential nested under a transport-named key still flips the flag), while `passw`/`pwd`/`passphrase`/`privatekey`/`token`/`secret`/`apikey` substrings are domain credentials (filtered, `replayable: false`). The default deliberately does not match a generic `key` suffix — an `idempotencyKey` is domain input a replay needs, not a secret; `apikey` is the exception — so an app-specific `otp`/`resetKey`-shaped field is added through the constructor (`new SensitiveParamsFilter(['otp'])`) or handled by its own `#[Filtered] ParamsFilterInterface` composing the default. A filter that throws does not break the request: nothing it failed on is recorded (`params: []`, `replayable: false`) and a warning is raised. `ResourceObservationModule`/`DevLogModule` accept and bind it explicitly (a trailing `paramsFilter` constructor parameter, after `module`, so 0.1.0 positional callers keep working); the `bear-observe` DevModule template maps it in its `toConstructor` call, without which Ray.Di ignores the parameter's `#[Filtered]` attribute. `SemanticLogMediaQueryLogger` honours the same `#[Filtered]` binding for the bind values it records (default `SensitiveParamsFilter`, replayability verdict dropped for a leaf), so both package recorders filter; response bodies, exception messages and an application's own loggers remain outside it
- `ResourceRequestContext::$replayable` / schema (optional, defaults to `true`) and `Event::$replayable` / `event_store.replayable` (see Migration above for `MediaQueryEventStore`): a request a filter marked non-replayable is recorded `replayable: false`, `SemanticLogExtractor` extracts it regardless — the event stream stays a complete record of what happened, the way Marten, Axon and Rails Event Store keep a masked event rather than drop it — and the flag travels onto the `Event` (excluded from `Event::$id`) and into every `EventStoreInterface`, because the log is transient and the store is what a replay engine reads. What that engine does with an event it cannot re-execute faithfully is its call; a re-append of an existing id keeps the stored value (first write wins, like every other column). A non-boolean value in a hand-built context is treated as not replayable, an explicit `null` included; only an absent field means replayable, since an entry predating the field genuinely was. Schema compatibility is one-way: the new `resource-request.json` accepts a log without the field, but the 0.1.0 schema (`additionalProperties: false`) rejects a log that carries it
- `SemanticLogInvoker` reads the recorded uri off the request's uri object instead of assembling `Request::toUri()` and cutting the query back off at the `?`. The round trip cost the request itself: `http_build_query()` raises a `ValueError` on a value it cannot stringify (an unbacked enum in the query), ahead of every guard in `invoke()`, so observation broke a request it only watches. It also stopped assigning the query onto the resource object's uri on the way past
- `skills/bear-observe/templates/*.php`: `#[Override]` on every generated method that overrides a parent class method or implements an interface method, so a strict downstream Psalm config (`ensureOverrideAttribute=true`) accepts the generated files. The README's own DevModule/AppModule wiring examples were updated to match
- `skills/bear-observe/harness/setup.php`: warns whenever an existing `DevModule` is merged rather than replaced (never on a fresh write or `--force`), because the shared-logger flush this warns about is as often wired through a different class the app's `DevModule` installs or overrides as it is named in the file itself
- `MediaQueryEventStoreTest`: the stale-`event_store_append.sql` half of the 0.1.0 sqlDir migration is now pinned by a test. Only the `event_store_list.sql` half was covered, and that one is the loud failure; the append half silently stores a non-replayable event as replayable via the column's `DEFAULT 1` — a known, documented limitation (see Migration above), not a bug this release fixes, but worth a regression test so the silent behavior itself cannot drift unnoticed

### Fixed

- A thrown exception's close no longer guesses `code: 500`: `code` is recorded only when the exception's own code is 400–599, and `SemanticLogExtractor` treats a missing `code` with an `exception` as a failure.
- `SemanticLogExtractor` reads semantic-logger's `log` document (was `entries`/`kind`); with the old reader, `extract()` returned no events.
- `SemanticLogInvoker::invoke()` no longer lets a throwing `SemanticLoggerInterface::open()` escape and break the request: the resource invocation now always runs, a broken `open()` only warns and skips the matching `close()` for that scope.
- `skills/bear-observe/harness/tree.php`: a `false` context value (e.g. `replayable`) rendered as an empty string, indistinguishable from a missing field, because `(string) false === ''`; booleans now render as the literal `true`/`false`. The hardcoded 60-character truncation was silent and had no way to confirm a value ended there rather than being cut — sensitive JSON below a `[FILTERED]` boundary could go unnoticed as a result — so a truncated value now reads `...(+N)` with the count of characters dropped, and a new `--full` flag disables truncation entirely. A fully-qualified class name value (e.g. `MyVendor\BeMart\Be\Input\AdminLoginInput`) now renders as its short class name instead of repeating the same namespace on every line
- Redaction documentation corrected against the code it describes: the per-key guarantee is bounded by `SensitiveParamsFilter::MAX_DEPTH` (past it the subtree is replaced whole, keys included), a filter failure leaves the entry in the log with an empty `params` rather than nothing at all, and the empty `params` serializes as `[]`, not `{}`. `EventTest` no longer claims a different filter keeps the event id — a filter that changes `params` changes the id, since params are hashed

### Changed

- `composer.json`: `bear/resource` below 1.31.0 now conflicts, since `SemanticLogInvoker` reads `AbstractRequest::$method` as the `Method` enum introduced in that release
- **BREAKING — `ResourceResponseContext`'s constructor is private and rejects a `code` outside 100-599.** Replace `new ResourceResponseContext($code, ...)` with `ResourceResponseContext::success($code, ...)`, or `ResourceResponseContext::failure($e, $code, ...)` for an escaped exception.
- **BREAKING — `EventStoreQueryInterface::append()` gained a required `int $replayable` parameter.** An application that implements this interface directly (rather than using the bundled `MediaQueryEventStore`) must add it; see Migration above for the matching SQL schema change.
- `koriym/semantic-logger` requires `^0.11`; examples and tests use the `log`/`close` shape.

### Note
- `ContextFreezer` itself round-trips with `JSON_PRESERVE_ZERO_FRACTION` as of `koriym/semantic-logger` 0.10.0 (semantic-logger#45), so `LogJson::toArray()`/`jsonSerialize()` preserve a whole-number float (`durationMs`, a price) as `float`. That fix does not reach this package's own consumers unchanged: `SemanticLogExtractor::extract()` and this package's test helpers re-encode the log through their own `json_encode()` (without that flag) to get a plain assoc-array view, which reintroduces the same degradation one step later. A consumer of a context field that may be a whole-number float must still accept `int|float`, not assume `float`

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
