# Changelog

## 0.1.0 (Unreleased)

### Added

- Event: immutable observed fact with a deterministic sha256 id derived from method, uri, UTC-normalized timestamp, and key-sorted params
- Events / EventsInterface: countable, iterable event collection
- SemanticLogExtractor: extracts events from Semantic Logger logs; gated to `resource_request` entries, requires an absolute ISO-8601 timestamp with an explicit offset, drops failed or uninterpretable response codes
- RecordedMethods: records POST/PUT/PATCH/DELETE by default, GET opt-in via `WITH_READS`
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

### Changed

- Require koriym/semantic-logger ^0.9
- Recording and extraction policies bind under separate keys (`#[Recorded]` / `#[Extracted]`), so dev GET recording no longer widens extraction
