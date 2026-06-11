<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;

use function strtoupper;

/** @psalm-import-type EventParams from Types */
final readonly class Event
{
    public string $method;

    /** @param EventParams $params */
    public function __construct(
        public string $uri,
        string $method,
        public DateTimeImmutable $timestamp,
        public array $params = [],
        public mixed $result = null,
    ) {
        $this->method = strtoupper($method);
    }
}
