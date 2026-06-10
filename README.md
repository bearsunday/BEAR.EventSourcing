# BEAR.EventSourcing

Event sourcing primitives extracted from Semantic Logger observations.

## Intent

This project starts from one constraint: **Semantic Logger is the observation source**.

The package does not observe BEAR.Resource directly, and it does not decorate `BEAR\Resource\LoggerInterface` to write to an event store during resource execution. It reads Semantic Logger observations and derives immutable event facts from them.

```text
Semantic Logger observations -> Events -> optional EventStore
```

## API

The package provides:

- `Event`: one immutable state-change fact
- `Events`: an iterable collection of events
- `Events::fromSemanticLog(array $log): Events`: extract events from a flushed Semantic Logger log
- `EventStoreInterface`: optional persistence port for extracted events

Persistence and framework integration are explicit application choices, not automatic runtime behavior.

## Event boundary

An event represents a successful state-changing resource-like operation observed in a Semantic Logger open/close pair.

Recorded methods:

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
