# BEAR.EventSourcing (WIP)

Event Sourcing for BEAR.Sunday — extracting facts from observations.

## Philosophy

BEAR.Sunday does not provide Event Sourcing as a feature. By following REST and AOP constraints, ES emerges naturally as a result.

> "A framework is a constraint, not a solution."

## Concept

SemanticLogger records meaningful observations. Event Sourcing extracts state-change facts from them.

```
Observation (SemanticLog) → Extraction → Facts (Events)
```

| Layer | Role | Persistence |
|-------|------|-------------|
| SemanticLog | Complete observation record | Configurable |
| Events | State-change facts only | Permanent |

Like a ship's log — observations may be temporary, but they trace the truth when needed.

## Installation

```bash
composer require bear/event-sourcing
```

Requires `bear/semantic-logger`.

## Interfaces

### Event

Immutable fact of a state change:

```php
final class Event
{
    public function __construct(
        public readonly string $id,
        public readonly string $timestamp,
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly mixed $result
    ) {}

    public static function fromEntry(array $entry): self;
    public function toArray(): array;
}
```

### EventsInterface

```php
interface EventsInterface extends IteratorAggregate
{
    public static function fromSemanticLog(array $semanticLog): self;
    public static function fromJson(string $json): self;
    public function toJson(): string;
    public function play(callable $handler): void;
}
```

### EventStoreInterface

```php
interface EventStoreInterface
{
    public function append(Event $event): void;
    public function getEvents(string $uri): EventsInterface;
    public function getEventsSince(string $timestamp): EventsInterface;
}
```

## Usage

### Extract from SemanticLog

```php
$semanticLog = $logger->flush();
$events = Events::fromSemanticLog($semanticLog);
```

### Query

```php
$events = $eventStore->getEvents('/users/1');

foreach ($events as $event) {
    // process event
}
```

### Replay

```php
$events->play(function(Event $e) use ($resource) {
    $resource->{$e->method}($e->uri, $e->params);
});
```

### Serialize

```php
// Export
file_put_contents('events.json', $events->toJson());

// Import and replay
Events::fromJson(file_get_contents('events.json'))->play($handler);
```

### Integration Testing

Replay production events for regression testing:

```php
$events = Events::fromJson(file_get_contents('production-events.json'));

$events->play(function(Event $e) use ($resource, $test) {
    $result = $resource->{$e->method}($e->uri, $e->params);
    $test->assertSame($e->result, $result->body);
});
```

Useful for:
- Bug reproduction
- Regression testing
- Production data verification

Assumes idempotency.

## Recording

AOP interceptor records SemanticLog and extracts Events:

```php
class SemanticLogInterceptor implements MethodInterceptor
{
    public function __construct(
        private SemanticLogger $logger,
        private EventStoreInterface $eventStore
    ) {}

    public function invoke(MethodInvocation $invocation)
    {
        $request = $invocation->getThis();
        $openContext = OpenContext::create($request);
        $this->logger->open($openContext);

        $result = $invocation->proceed();

        $completeContext = CompleteContext::create($result, $openContext);
        $this->logger->close($completeContext);

        if ($request->method !== 'GET') {
            $this->eventStore->append(Event::fromCompleteContext($completeContext));
        }

        return $result;
    }
}
```

### Why AOP?

| Approach | Cost |
|----------|------|
| AOP | Only bound resources |
| Invoker | All requests |

Benefits:
- Selective application via attributes
- No cache interceptor conflicts (GET excluded)
- Failed authentication = no event recorded
- No global state

## Module

```php
class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)->to(RedisEventStore::class);
        
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(EventSourced::class),
            [SemanticLogInterceptor::class]
        );
    }
}
```

## Package Structure

```
BEAR.SemanticLogger (observation)
        ↓
BEAR.EventSourcing (extraction)
```

| Package | Responsibility |
|---------|----------------|
| BEAR.SemanticLogger | Observations — router, resource, everything |
| BEAR.EventSourcing | State-change facts — extraction & persistence |

EventSourcing depends on SemanticLogger. Not vice versa.

## Design Principles

| Principle | Application |
|-----------|-------------|
| WYSIWYG | Observation is truth |
| Separation | Observation → Fact |
| Single Responsibility | EventStore stores and retrieves |
| Symmetry | `fromJson`/`toJson`, `fromSemanticLog` |
| Transparency | Resources unaware of ES |
| No Global State | DI injection, no order dependency |

## Vision

SemanticLogger captures micro to macro in one structure:

| Layer | Target |
|-------|--------|
| Micro | XHProf, Xdebug, PHP profile |
| Resource | Request/Response, state changes |
| Macro | Hypermedia links, workflow intent |

Event Sourcing extracts one aspect: **state changed**.

### Observation vs Fact

| | Nature |
|---|--------|
| SemanticLog | Meaning — structure, context, intent |
| Events | Fact — state changed, nothing more |

Extraction: meaning → fact. Context discarded, essence kept.

Replay needs facts. Analysis needs context. Both complete the picture.

### For Humans and Machines

- JSON Schema typed
- Self-proving responses
- AI and developer readable

Same philosophy as "Everything is a Resource" — one abstraction unifies all.
