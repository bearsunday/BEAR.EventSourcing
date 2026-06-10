<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;
use JsonSerializable;

use function strtoupper;

/**
 * @psalm-import-type EventInput from Types
 * @psalm-import-type EventOutput from Types
 * @psalm-import-type EventParams from Types
 * @phpstan-import-type EventInput from Types
 * @phpstan-import-type EventOutput from Types
 * @phpstan-import-type EventParams from Types
 */
final readonly class Event implements JsonSerializable
{
    public const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

    public string $method;
    public DateTimeImmutable $timestamp;

    /** @param EventParams $params */
    public function __construct(
        public string $uri,
        string $method,
        public array $params = [],
        public mixed $result = null,
        DateTimeImmutable|null $timestamp = null,
    ) {
        $this->method = strtoupper($method);
        $this->timestamp = $timestamp ?? new DateTimeImmutable();
    }

    /** @param EventParams $params */
    public static function create(
        string $uri,
        string $method,
        array $params = [],
        mixed $result = null,
        DateTimeImmutable|null $timestamp = null,
    ): self {
        return new self($uri, $method, $params, $result, $timestamp);
    }

    /** @param EventInput $data */
    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            method: $data['method'],
            params: $data['params'] ?? [],
            result: $data['result'] ?? null,
            timestamp: isset($data['timestamp']) ? new DateTimeImmutable($data['timestamp']) : null,
        );
    }

    /** @return EventOutput */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'params' => $this->params,
            'result' => $this->result,
            'timestamp' => $this->timestamp->format(self::TIMESTAMP_FORMAT),
        ];
    }

    /** @return EventOutput */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
