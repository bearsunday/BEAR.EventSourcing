<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Countable;
use DateTimeInterface;
use IteratorAggregate;

/**
 * @extends IteratorAggregate<int, Event>
 */
interface EventsInterface extends IteratorAggregate, Countable
{
    public static function fromJson(string $json): self;

    /**
     * @param array<string, mixed> $semanticLog Output of Koriym\SemanticLogger\LogJson::toArray()
     */
    public static function fromSemanticLog(array $semanticLog): self;

    public function toJson(): string;

    public function add(Event $event): self;

    public function filterByUri(string $pattern): self;

    public function filterByMethod(string $method): self;

    public function since(DateTimeInterface $since): self;

    /**
     * @param callable(Event): void $handler
     */
    public function replay(callable $handler): void;

    /**
     * @return Event[]
     */
    public function all(): array;
}
