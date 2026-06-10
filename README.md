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

Recorded methods:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

`GET` is observation data, not an event.
