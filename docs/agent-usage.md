# AI agent usage

Use this package as an explicit observation, extraction, and replay library.

```text
BEAR.Resource Invoker -> Semantic Logger LogJson -> SemanticLogExtractor -> EventsInterface -> optional EventStoreInterface
```

## Agent workflow

1. Start from a `Koriym\SemanticLogger\LogJson` produced by `SemanticLogger::flush()`.
2. If BEAR.Resource calls need to be observed, install `ResourceObservationModule` as an Invoker decorator.
3. Inject or instantiate `SemanticLogExtractorInterface`.
4. Call `$extractor->extract($logJson)`.
5. Iterate `EventsInterface` with `foreach` or standard SPL iterators.
6. Store events only when the application explicitly provides an `EventStoreInterface`.

Do not add a BEAR.Resource logger decorator or runtime auto-persistence. Semantic Logger remains the observation source; EventStore remains an optional destination.

## BEAR.Resource observation

Use `ResourceObservationModule` when the application wants Semantic Logger open/close entries from BEAR.Resource execution:

```php
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new ResourceObservationModule(
    module: new ResourceClientModule(),
));
```

The default `NullViewStore` does not render or save views. If a debug workflow needs payload access, provide `ViewStoreInterface`; the Semantic Log should contain `view_ref`, not an inline body.

For local development and AI debugging, prefer `DevLogModule`:

```php
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\Resource\Module\ResourceClientModule;
use Ray\Di\Injector;

$injector = new Injector(new DevLogModule(
    viewDir: __DIR__ . '/var/es/views',
    module: new ResourceClientModule(),
));
```

It clears `viewDir` at injector creation, stores rendered views through `FileViewStore`, and records reads with `RecordedMethods::WITH_READS`.

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
