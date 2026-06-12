# AGENTS.md

## Project direction

This repository implements event sourcing by extracting immutable event facts from Semantic Logger observations.

Semantic Logger is the observation source. BEAR.Resource runtime observation, when needed, must be an optional `InvokerInterface` bridge that writes Semantic Logger open/close entries. Do not decorate `BEAR\Resource\LoggerInterface` to persist events during request execution.

Preferred flow:

```text
Semantic Logger observations -> Events -> optional EventStore
```

## Dependency policy

- Require `koriym/semantic-logger` for observations and `ray/media-query` for SQL EventStore support.
- Do not copy Semantic Logger code into this repository.
- If Semantic Logger behavior must be changed temporarily, isolate it with `cweagans/composer-patches` and files under `patches/`.
- Upstream Semantic Logger changes later as a separate PR.

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
- Semantic Logger log fixtures and extractor tests
- `EventStoreInterface`, in-memory implementation, and Ray.MediaQuery SQL implementation
- `EventSourcingModule` and `MediaQueryEventStoreModule` for optional ES bindings
- Optional BEAR.Resource observation bridge using `InvokerInterface`
- Development `FileViewStore` / `DevResourceObservationModule` for local view refs

Keep out of this package:

- BEAR.Resource logger decorator
- Runtime auto-persistence
- Inline BEAR.Resource view/body persistence policy; use `ViewStoreInterface` and `view_ref`
- Application-specific BEAR module wiring
- Hidden installation of application-owned MediaQuery or database modules from ES modules
- Application-specific domain code
- Bundled subpackages
