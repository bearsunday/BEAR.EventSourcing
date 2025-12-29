# BEAR.SemanticLogger

Semantic logging system for BEAR.Sunday with structured profiling.

## Philosophy

> "Observation is the foundation of understanding."

SemanticLogger captures **meaningful observations** of resource operations — not just what happened, but the context and intent behind each action.

## Concept

```
Resource Request → SemanticInvoker → Observation (LogJson)
                                          ↓
                              EventExtractorInterface (optional)
                                          ↓
                              BEAR.EventSourcing (extraction)
```

| Layer | Role |
|-------|------|
| Context | Structured observation data |
| Invoker | Wraps resource operations with open/close lifecycle |
| EventExtractor | Bridge to Event Sourcing (optional) |

## Installation

```bash
composer require bear/semantic-logger
```

## Interfaces

### OpenContextInterface

Captures the intent of a resource request:

```php
interface OpenContextInterface
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getParams(): array;
}
```

### CompleteContextInterface

Captures the result of a resource request:

```php
interface CompleteContextInterface
{
    public function getCode(): int;
    public function getHeaders(): array;
    public function getBody(): mixed;
    public function getView(): ?string;
}
```

### EventExtractorInterface

Bridge for Event Sourcing integration:

```php
interface EventExtractorInterface
{
    public function extract(
        OpenContextInterface $open,
        CompleteContextInterface $complete
    ): void;
}
```

## Usage

### Module Installation

```php
// Basic semantic logging
$this->install(new SemanticLoggerModule());

// With Event Sourcing integration
$this->install(new SemanticLoggerModule($eventExtractor));
```

### SemanticInvoker

The `SemanticInvoker` wraps resource invocations:

```php
class SemanticInvoker implements InvokerInterface
{
    public function invoke(AbstractRequest $request)
    {
        $openContext = $this->contextFactory->createOpenContext($request);
        $this->logger->open($openContext);

        try {
            $result = $this->invoker->invoke($request);
            $completeContext = $this->contextFactory->createCompleteContext($result);
            $this->logger->close($completeContext);

            // Real-time extraction (if configured)
            $this->extractor?->extract($openContext, $completeContext);

            return $result;
        } catch (\Throwable $e) {
            $errorContext = $this->contextFactory->createErrorContext($e);
            $this->logger->close($errorContext);
            throw $e;
        }
    }
}
```

## Integration with BEAR.EventSourcing

This package provides `EventExtractorInterface` which BEAR.EventSourcing implements:

```php
// In BEAR.EventSourcing
class EventStoreExtractor implements EventExtractorInterface
{
    public function extract(
        OpenContextInterface $open,
        CompleteContextInterface $complete
    ): void {
        if ($open->getMethod() === 'GET') {
            return; // No state change
        }

        $this->eventStore->append(
            Event::fromContexts($open, $complete)
        );
    }
}
```

## Design Principles

| Principle | Application |
|-----------|-------------|
| Separation of Concerns | Observation vs Extraction |
| Dependency Inversion | Interface in this package, implementation in EventSourcing |
| Open/Closed | Extend via EventExtractorInterface |
| Single Responsibility | Each context captures one aspect |
