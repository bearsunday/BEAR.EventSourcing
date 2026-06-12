---
name: event-sourcing
description: Work with BEAR.EventSourcing integrations, examples, or reviews. Use this skill whenever editing this repository's event extraction flow, extracting events from Koriym Semantic Logger LogJson, iterating EventsInterface, filtering/replaying events, wiring EventStoreInterface, reviewing EventStore module boundaries, or deciding how an AI agent should use this package.
---

# BEAR.EventSourcing

Use Semantic Logger as the observation source and EventStore as an optional destination.

```text
Koriym\SemanticLogger\LogJson -> SemanticLogExtractorInterface -> EventsInterface -> optional EventStoreInterface
```

## Workflow

1. Read `README.md` for the public API and `docs/agent-usage.md` for agent-specific guidance.
2. Use `SemanticLogExtractorInterface::extract(LogJson $semanticLog)` to create events.
3. Iterate events with `foreach`, `EventsInterface::getIterator()`, or SPL iterators.
4. Use `RecordedMethods::WITH_READS` only for development-time GET tracing.
5. Persist with `EventStoreInterface` only when the application explicitly chooses storage.

## Boundaries

Do:

- Keep `Event`, `EventsInterface`, and `Events` minimal.
- Use `CallbackFilterIterator` or other iterators for method, URI, parameter, and timestamp selection.
- Keep Ray.MediaQuery and database installation in the application.
- Use `InMemoryEventStore` for examples and tests.

Do not:

- Add a BEAR.Resource logger decorator.
- Persist automatically during runtime observation.
- Make MCP, CLI, or skills part of the core runtime contract.
- Hide `AuraSqlModule` or `MediaQuerySqlModule` inside EventSourcing modules.

## Examples

Use the bundled examples before inventing new integration code:

```bash
php examples/extract.php
php examples/store.php
php examples/replay.php
```

`examples/semantic-log.json` is a public Semantic Logger tree fixture for inspection. The runnable examples build a `LogJson` through `SemanticLogger::flush()` and then extract events.

## Validation

For library changes, run:

```bash
composer tests
composer psalm
composer require-checker
composer validate --strict
```
