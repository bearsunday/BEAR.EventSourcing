<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use DateTimeImmutable;
use JsonSerializable;

/**
 * Immutable event representing a state change.
 *
 * Events are facts that have occurred and cannot be changed.
 */
final class Event implements JsonSerializable
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public readonly string $id,
        public readonly string $timestamp,
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly mixed $result,
    ) {
    }

    public static function fromContexts(
        AbstractOpenContext $open,
        AbstractCompleteContext $complete,
    ): self {
        return new self(
            id: self::generateId(),
            timestamp: (new DateTimeImmutable())->format('c'),
            uri: $open->uri,
            method: $open->method,
            params: $open->params,
            result: $complete->body,
        );
    }

    /**
     * @param array{id: string, timestamp: string, uri: string, method: string, params: array<string, mixed>, result: mixed} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            timestamp: $data['timestamp'],
            uri: $data['uri'],
            method: $data['method'],
            params: $data['params'],
            result: $data['result'],
        );
    }

    /**
     * @return array{id: string, timestamp: string, uri: string, method: string, params: array<string, mixed>, result: mixed}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'uri' => $this->uri,
            'method' => $this->method,
            'params' => $this->params,
            'result' => $this->result,
        ];
    }

    /**
     * @return array{id: string, timestamp: string, uri: string, method: string, params: array<string, mixed>, result: mixed}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function generateId(): string
    {
        return sprintf('%08x%08x', time(), random_int(0, 0xFFFFFFFF));
    }
}
