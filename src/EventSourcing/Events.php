<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use ArrayIterator;
use DateTimeInterface;
use Traversable;

/**
 * Events collection implementation
 */
final class Events implements EventsInterface
{
    /** @var Event[] */
    private array $events = [];

    /**
     * @param Event[] $events
     */
    public function __construct(array $events = [])
    {
        $this->events = $events;
    }

    /**
     * @inheritDoc
     */
    public static function fromJson(string $json): EventsInterface
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        $events = array_map(
            fn(array $item) => Event::fromArray($item),
            $data
        );

        return new self($events);
    }

    /**
     * @inheritDoc
     */
    public function toJson(): string
    {
        return json_encode(
            array_map(fn(Event $e) => $e->toArray(), $this->events),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @inheritDoc
     */
    public function add(Event $event): EventsInterface
    {
        $events = $this->events;
        $events[] = $event;

        return new self($events);
    }

    /**
     * @inheritDoc
     */
    public function filterByUri(string $pattern): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            fn(Event $e) => fnmatch($pattern, $e->uri)
        );

        return new self(array_values($filtered));
    }

    /**
     * @inheritDoc
     */
    public function filterByMethod(string $method): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            fn(Event $e) => strcasecmp($e->method, $method) === 0
        );

        return new self(array_values($filtered));
    }

    /**
     * @inheritDoc
     */
    public function since(DateTimeInterface $since): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            fn(Event $e) => $e->timestamp >= $since
        );

        return new self(array_values($filtered));
    }

    /**
     * @inheritDoc
     */
    public function replay(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->events;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->events);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }
}
