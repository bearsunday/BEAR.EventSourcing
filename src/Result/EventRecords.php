<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Result;

use ArrayIterator;
use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\EventSourcing\EventSourcing\Events;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use Countable;
use IteratorAggregate;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use Traversable;

use function array_map;
use function count;

/**
 * @template T of EventRecord
 * @implements IteratorAggregate<int, T>
 */
final readonly class EventRecords implements Countable, IteratorAggregate, PostQueryInterface
{
    /** @param list<T> $records */
    public function __construct(
        private array $records,
    ) {
    }

    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<T> $records */
        $records = $context->rows;

        return new self($records);
    }

    public function toEvents(): EventsInterface
    {
        return new Events(array_map(
            static fn (EventRecord $record): Event => $record->toEvent(),
            $this->records,
        ));
    }

    /** @return Traversable<int, T> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }

    public function count(): int
    {
        return count($this->records);
    }
}
