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
- `SemanticLogExtractorInterface` / `SemanticLogExtractor`: extract events from a flushed Semantic Logger log
- `RecordedMethods`: injectable extraction policy for recorded methods
- `EventStoreInterface`: optional persistence port for extracted events

Persistence and framework integration are explicit application choices, not automatic runtime behavior.

## Event boundary

An event represents a successful state-changing resource-like operation observed in a Semantic Logger open/close pair.

Recorded methods by default:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

`GET` is observation data by default. Applications can include it explicitly for development-time read tracing by injecting a different `RecordedMethods` policy.

## Usage

Extract events from a flushed Semantic Logger log through an injected extractor:

```php
use BEAR\EventSourcing\SemanticLogExtractorInterface;

$log = $semanticLogger->flush()->toArray();

/** @var SemanticLogExtractorInterface $extractor */
$events = $extractor->extract($log);

foreach ($events as $event) {
    // append to an EventStore, replay, or inspect
}
```

Configure the extractor through dependency injection. Bind `RecordedMethods` with reads included for development-time tracing:

```php
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;

$extractor = new SemanticLogExtractor(new RecordedMethods(RecordedMethods::WITH_READS));
$events = $extractor->extract($log);
```

The extractor expects an `open` tree where each operation has a context with:

- `uri`: resource-like identifier
- `method`: HTTP-style method
- `query` or `params`: operation input
- `timestamp`: optional ISO-8601 timestamp

The matching `close.context.body` becomes the event result. If `close.context.code` exists and is `400` or greater, the operation is treated as unsuccessful and ignored.

Serialize and restore events:

```php
$json = $events->toJson();
$restored = Events::fromJson($json);
```

Replay events through an application handler:

```php
$events->replay(static function (Event $event) use ($handler): void {
    $handler($event->method, $event->uri, $event->params);
});
```

Filter events:

```php
$writesToUsers = $events->filterByUri('app://self/users*');
$posts = $events->filterByMethod('POST');
```

Persist extracted events explicitly when an application needs storage:

```php
use BEAR\EventSourcing\InMemoryEventStore;

$store = new InMemoryEventStore();
$store->appendAll($events);
```

`EventStoreInterface` is intentionally small. It is a persistence port for already-extracted events, not a runtime hook.
