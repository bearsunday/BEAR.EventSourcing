<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Result;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\EventSourcing\EventSourcing\Events;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use DateTimeInterface;
use InvalidArgumentException;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use Traversable;

use function get_debug_type;
use function sprintf;

final readonly class StoredEvents implements EventsInterface, PostQueryInterface
{
    public function __construct(
        private EventsInterface $events,
    ) {
    }

    public static function fromContext(PostQueryContext $context): static
    {
        $events = [];
        foreach ($context->rows as $index => $row) {
            if (! $row instanceof StoredEvent) {
                throw new InvalidArgumentException(sprintf(
                    'Expected StoredEvent at row %d, got %s',
                    $index,
                    get_debug_type($row),
                ));
            }

            $events[] = $row->toEvent();
        }

        return new self(new Events($events));
    }

    /** @inheritDoc */
    public static function fromJson(string $json): EventsInterface
    {
        return Events::fromJson($json);
    }

    /** @inheritDoc */
    public function toJson(): string
    {
        return $this->events->toJson();
    }

    /** @inheritDoc */
    public function add(Event $event): EventsInterface
    {
        return $this->events->add($event);
    }

    /** @inheritDoc */
    public function filterByUri(string $pattern): EventsInterface
    {
        return $this->events->filterByUri($pattern);
    }

    /** @inheritDoc */
    public function filterByMethod(string $method): EventsInterface
    {
        return $this->events->filterByMethod($method);
    }

    /** @inheritDoc */
    public function since(DateTimeInterface $since): EventsInterface
    {
        return $this->events->since($since);
    }

    /** @inheritDoc */
    public function replay(callable $handler): void
    {
        $this->events->replay($handler);
    }

    /** @inheritDoc */
    public function all(): array
    {
        return $this->events->all();
    }

    /** @return Traversable<int, Event> */
    public function getIterator(): Traversable
    {
        return $this->events->getIterator();
    }

    public function count(): int
    {
        return $this->events->count();
    }
}
