<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use ArrayIterator;
use BEAR\SemanticLogger\ResourceRequestContext;
use BEAR\SemanticLogger\ResourceResponseContext;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function fnmatch;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function strcasecmp;
use function strtoupper;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Events collection implementation
 */
final readonly class Events implements EventsInterface
{
    private const RECORDED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @param Event[] $events */
    public function __construct(private array $events = [])
    {
    }

    /** @inheritDoc */
    public static function fromJson(string $json): EventsInterface
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON data');
        }

        $events = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid JSON data');
            }

            $events[] = Event::fromArray($item);
        }

        return new self($events);
    }

    /** @param array<array-key, mixed> $semanticLog */
    public static function fromSemanticLog(array $semanticLog): self
    {
        $open = $semanticLog['open'] ?? [];
        if (! is_array($open)) {
            return new self();
        }

        $events = [];
        self::walk($open, $events);

        return new self($events);
    }

    /** @inheritDoc */
    public function toJson(): string
    {
        return json_encode(
            array_map(static fn (Event $e): array => $e->toArray(), $this->events),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /** @inheritDoc */
    public function add(Event $event): EventsInterface
    {
        $events = $this->events;
        $events[] = $event;

        return new self($events);
    }

    /** @inheritDoc */
    public function filterByUri(string $pattern): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => fnmatch($pattern, $e->uri),
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function filterByMethod(string $method): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => strcasecmp($e->method, $method) === 0,
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function since(DateTimeInterface $since): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => $e->timestamp >= $since,
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function replay(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    /** @inheritDoc */
    public function all(): array
    {
        return $this->events;
    }

    /** @inheritDoc */
    public function count(): int
    {
        return count($this->events);
    }

    /** @inheritDoc */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    /**
     * @param array<array-key, mixed> $opens
     * @param Event[]                 $events
     */
    private static function walk(array $opens, array &$events): void
    {
        foreach ($opens as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            self::appendEvent($entry, $events);

            $children = $entry['open'] ?? [];
            if (! is_array($children)) {
                continue;
            }

            self::walk($children, $events);
        }
    }

    /**
     * @param array<array-key, mixed> $entry
     * @param Event[]                 $events
     */
    private static function appendEvent(array $entry, array &$events): void
    {
        $context = self::requestContext($entry);
        $closeContext = self::responseContext($entry);
        if ($context === null || $closeContext === null) {
            return;
        }

        $method = self::recordedMethod($context);
        $uri = self::stringContextValue($context, 'uri');
        if ($method === null || $uri === null) {
            return;
        }

        $events[] = Event::create(
            $uri,
            $method,
            self::arrayContextValue($context, 'query'),
            $closeContext['body'] ?? null,
            self::timestampContextValue($context),
        );
    }

    /**
     * @param array<array-key, mixed> $entry
     *
     * @return array<array-key, mixed>|null
     */
    private static function requestContext(array $entry): array|null
    {
        if (($entry['type'] ?? null) !== ResourceRequestContext::TYPE) {
            return null;
        }

        $context = $entry['context'] ?? null;

        return is_array($context) ? $context : null;
    }

    /**
     * @param array<array-key, mixed> $entry
     *
     * @return array<array-key, mixed>|null
     */
    private static function responseContext(array $entry): array|null
    {
        $close = $entry['close'] ?? null;
        if (! is_array($close) || ($close['type'] ?? null) !== ResourceResponseContext::TYPE) {
            return null;
        }

        $context = $close['context'] ?? null;

        return is_array($context) ? $context : null;
    }

    /** @param array<array-key, mixed> $context */
    private static function recordedMethod(array $context): string|null
    {
        $methodValue = $context['method'] ?? null;
        if (! is_string($methodValue)) {
            return null;
        }

        $method = strtoupper($methodValue);

        return in_array($method, self::RECORDED_METHODS, true) ? $method : null;
    }

    /** @param array<array-key, mixed> $context */
    private static function stringContextValue(array $context, string $key): string|null
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    private static function arrayContextValue(array $context, string $key): array
    {
        $value = $context[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /** @param array<array-key, mixed> $context */
    private static function timestampContextValue(array $context): DateTimeImmutable|null
    {
        $timestamp = self::stringContextValue($context, 'timestamp');

        return $timestamp !== null && $timestamp !== '' ? new DateTimeImmutable($timestamp) : null;
    }
}
