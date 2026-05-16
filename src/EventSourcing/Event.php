<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeImmutable;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

/**
 * Immutable Event class representing a state change
 */
final class Event implements JsonSerializable
{
    private function __construct(
        public readonly string $id,
        public readonly DateTimeImmutable $timestamp,
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly mixed $result,
    ) {
    }

    /**
     * Create a new event from a resource request
     *
     * @param string              $uri    Resource URI
     * @param string              $method HTTP method
     * @param array<string,mixed> $params Request parameters
     * @param mixed               $result Request result
     */
    public static function create(
        string $uri,
        string $method,
        array $params,
        mixed $result
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            new DateTimeImmutable(),
            $uri,
            $method,
            $params,
            $result
        );
    }

    /**
     * Create an event from array data
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            new DateTimeImmutable($data['timestamp']),
            $data['uri'],
            $data['method'],
            $data['params'] ?? [],
            $data['result'] ?? null
        );
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'uri' => $this->uri,
            'method' => $this->method,
            'params' => $this->params,
            'result' => $this->result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
