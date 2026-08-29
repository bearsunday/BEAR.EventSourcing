<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\SemanticLogExtractor;
use Koriym\SemanticLogger\AbstractContext;

final class ResourceRequestContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = SemanticLogExtractor::RESOURCE_REQUEST_TYPE;

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/resource-request.json';

    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly string $timestamp,
    ) {
    }
}
