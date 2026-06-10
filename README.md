# event-sourcing

Event sourcing primitives extracted from Semantic Logger observations.

## Intent

This project starts from one constraint: the Semantic Logger log is the observation source.

The package does not observe BEAR.Resource directly, and it does not decorate `BEAR\Resource\LoggerInterface` to write to an event store during resource execution. It reads Semantic Logger observations and derives immutable event facts from them.

```text
Semantic Logger observations -> Events -> optional EventStore
```

## First API shape

The first implementation should stay small:

- `Event`: one immutable state-change fact
- `Events`: a collection of events
- `Events::fromSemanticLog(array $log): Events`: extract events from a flushed Semantic Logger log

No runtime module, SQL store, framework hook, or application prototype belongs in the first step.

## Event boundary

An event represents a successful state-changing resource operation.

Initial recorded methods:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

`GET` is observation data, not an event.

## Non-goals for the first iteration

- No EC-CUBE prototype
- No monorepo or bundled subpackages
- No direct `BEAR\Resource\LoggerInterface` decorator
- No automatic persistence during request execution
- No SQL EventStore yet
- No BeMart integration in this repository

## Build order

1. README only
2. `Event` / `Events` value objects
3. Semantic log fixtures and extractor
4. Optional persistence port
5. Optional EventStore implementations
6. Application experiments outside this repository

## Review discipline

Keep each step small. Commit one idea, review it, then continue.
