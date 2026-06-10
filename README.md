# BEAR.EventSourcing

Event sourcing primitives extracted from Semantic Logger observations.

## Intent

This project starts from one constraint: **Semantic Logger is the observation source**.

The package does not observe BEAR.Resource directly, and it does not decorate `BEAR\Resource\LoggerInterface` to write to an event store during resource execution. It reads Semantic Logger observations and derives immutable event facts from them.

```text
Semantic Logger observations -> Events -> optional EventStore
```

## First API shape

The implementation starts small:

- `Event`: one immutable state-change fact
- `Events`: an iterable collection of events
- `Events::fromSemanticLog(array $log): Events`: extract events from a flushed Semantic Logger log
- `EventStoreInterface`: optional persistence port for extracted events

No runtime module, SQL store, framework hook, or application prototype belongs in the first iteration.

## Event boundary

An event represents a successful state-changing resource-like operation observed in a Semantic Logger open/close pair.

Initial recorded methods:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

`GET` is observation data, not an event.

## Usage sketch

```php
use BEAR\EventSourcing\Events;

$log = $semanticLogger->flush()->toArray();
$events = Events::fromSemanticLog($log);

foreach ($events as $event) {
    // append to an EventStore, replay, or inspect
}
```

## Non-goals for the first iteration

- No EC-CUBE prototype
- No monorepo or bundled subpackages
- No direct `BEAR\Resource\LoggerInterface` decorator
- No automatic persistence during request execution
- No SQL EventStore yet
- No BeMart integration in this repository

## Build order

1. README and project instructions
2. Composer skeleton and QA tools
3. `Event` / `Events` value objects
4. Semantic log fixtures and extractor
5. Optional persistence port
6. Application experiments outside this repository

## Review discipline

Keep each step small. Commit one idea, review it, then continue.
