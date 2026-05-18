<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

final class Event implements JsonSerializable
{
    public const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

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
     * @param array<string, mixed> $params
     */
    public static function create(
        string $uri,
        string $method,
        array $params,
        mixed $result,
        DateTimeImmutable|null $timestamp = null,
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $timestamp ?? new DateTimeImmutable(),
            $uri,
            $method,
            $params,
            $result,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['id', 'timestamp', 'uri', 'method'] as $required) {
            if (! array_key_exists($required, $data)) {
                throw new InvalidArgumentException(sprintf('Missing required key: %s', $required));
            }
        }

        return new self(
            $data['id'],
            new DateTimeImmutable($data['timestamp']),
            $data['uri'],
            $data['method'],
            $data['params'] ?? [],
            $data['result'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format(Event::TIMESTAMP_FORMAT),
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
