<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Result;

use BEAR\EventSourcing\EventSourcing\Event;
use UnexpectedValueException;

use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final readonly class StoredEvent
{
    public function __construct(
        public string $id,
        public string $timestamp,
        public string $uri,
        public string $method,
        public string|null $params,
        public string|null $result,
    ) {
    }

    public static function factory(
        string $id,
        string $timestamp,
        string $uri,
        string $method,
        string|null $params,
        string|null $result,
    ): self {
        return new self($id, $timestamp, $uri, $method, $params, $result);
    }

    public function toEvent(): Event
    {
        return Event::reconstitute(
            $this->id,
            $this->timestamp,
            $this->uri,
            $this->method,
            $this->decodeParams(),
            json_decode($this->result ?? 'null', true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<array-key, mixed> */
    private function decodeParams(): array
    {
        $params = json_decode($this->params ?? '[]', true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($params)) {
            throw new UnexpectedValueException('Invalid event store params');
        }

        return $params;
    }
}
