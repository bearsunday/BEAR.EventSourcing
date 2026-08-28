<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use Koriym\SemanticLogger\AbstractContext;

final class ResourceRequestContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'resource_request';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-request.json';

    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly string $timestamp,
    ) {
    }
}
