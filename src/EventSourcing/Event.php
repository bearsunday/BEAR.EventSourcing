<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

use function is_array;
use function is_string;

/**
 * Immutable Event class representing a state change
 */
final readonly class Event implements JsonSerializable
{
    /** @param array<array-key, mixed> $params */
    private function __construct(
        public string $id,
        public DateTimeImmutable $timestamp,
        public string $uri,
        public string $method,
        public array $params,
        public mixed $result,
    ) {
    }

    /**
     * Create a new event from a resource request
     *
     * @param string               $uri    Resource URI
     * @param string               $method HTTP method
     * @param array<string, mixed> $params Request parameters
     * @param mixed                $result Request result
     */
    public static function create(
        string $uri,
        string $method,
        array $params,
        mixed $result,
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            new DateTimeImmutable(),
            $uri,
            $method,
            $params,
            $result,
        );
    }

    /**
     * Create an event from array data
     *
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $timestamp = $data['timestamp'] ?? null;
        $uri = $data['uri'] ?? null;
        $method = $data['method'] ?? null;
        $params = $data['params'] ?? [];

        if (! is_string($id) || ! is_string($timestamp) || ! is_string($uri) || ! is_string($method) || ! is_array($params)) {
            throw new InvalidArgumentException('Invalid event data');
        }

        return self::reconstitute(
            $id,
            $timestamp,
            $uri,
            $method,
            $params,
            $data['result'] ?? null,
        );
    }

    /**
     * Reconstitute an event from persisted values.
     *
     * @param array<array-key, mixed> $params
     */
    public static function reconstitute(
        string $id,
        string $timestamp,
        string $uri,
        string $method,
        array $params,
        mixed $result,
    ): self {
        return new self(
            $id,
            new DateTimeImmutable($timestamp),
            $uri,
            $method,
            $params,
            $result,
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
