# AGENTS.md

## Project direction

This repository implements event sourcing by extracting immutable event facts from Semantic Logger observations.

Semantic Logger is the observation source. Do not directly observe BEAR.Resource runtime behavior, and do not decorate `BEAR\Resource\LoggerInterface` to persist events during request execution.

Preferred flow:

```text
Semantic Logger observations -> Events -> optional EventStore
```

## Dependency policy

- Require `koriym/semantic-logger` as the external Composer dependency.
- Do not vendor or monorepo Semantic Logger code into this repository.
- If Semantic Logger behavior must be changed temporarily, isolate it with `cweagans/composer-patches` and files under `patches/`.
- Upstream Semantic Logger changes later as a separate PR.

## Implementation discipline

- Keep each commit focused on one concept.
- Prefer README and tests before expanding implementation.
- Keep public API minimal until validated by usage.
- Do not add application prototypes to this repository.

## Initial scope

Allowed early components:

- `Event`
- `Events`
- `Events::fromSemanticLog(array $log)`
- Semantic Logger log fixtures and extractor tests
- Optional `EventStoreInterface` and in-memory implementation

Out of scope initially:

- BEAR.Resource logger decorator
- Runtime auto-persistence
- SQL EventStore
- BEAR module wiring
- EC-CUBE / BeMart application code
- Monorepo subpackages

## Review discipline

Before each commit:

- Confirm README still matches implementation.
- Confirm no application prototype code has been introduced.
- Confirm any Semantic Logger workaround is isolated as a Composer patch.
- Run the relevant local QA command for the files changed.
