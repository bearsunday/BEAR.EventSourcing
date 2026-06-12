# AI agent usage

Use this package as an explicit extraction and replay library.

```text
Semantic Logger LogJson -> SemanticLogExtractor -> EventsInterface -> optional EventStoreInterface
```

## Agent workflow

1. Start from a `Koriym\SemanticLogger\LogJson` produced by `SemanticLogger::flush()`.
2. Inject or instantiate `SemanticLogExtractorInterface`.
3. Call `$extractor->extract($logJson)`.
4. Iterate `EventsInterface` with `foreach` or standard SPL iterators.
5. Store events only when the application explicitly provides an `EventStoreInterface`.

Do not add a BEAR.Resource logger decorator or runtime auto-persistence. Semantic Logger remains the observation source; EventStore remains an optional destination.

## Development reads

`GET` is ignored by default. Include reads only for development-time tracing:

```php
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;

$extractor = new SemanticLogExtractor(new RecordedMethods(RecordedMethods::WITH_READS));
$events = $extractor->extract($logJson);
```

## Filtering and replay

Keep `Events` small. Use iterators for selection:

```php
use BEAR\EventSourcing\Event;

$forUser = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => ($event->params['id'] ?? null) === 'koriym',
);

foreach ($forUser as $event) {
    // replay, project, inspect
}
```

Stack filters for method, URI, parameter, or timestamp selection.

## Storage

Use `InMemoryEventStore` for tests and examples. Use `MediaQueryEventStore` only when the application already owns Ray.MediaQuery and database setup. `EventSourcingModule` may install an EventStore binding, but it must not hide application-owned database or MediaQuery modules.

## Examples

Run:

```bash
php examples/extract.php
php examples/store.php
php examples/replay.php
```

Read `examples/semantic-log.json` as a public-tree fixture that mirrors the generated example log. For Claude Code, use `.claude/skills/event-sourcing/SKILL.md` as the project-local skill.
