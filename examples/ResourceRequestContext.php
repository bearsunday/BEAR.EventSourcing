<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use Koriym\SemanticLogger\AbstractContext;

// Example-only context: uses 'query' to match the fixture JSON keys in semantic-log.json.
// The production equivalent in src/Resource/ResourceRequestContext.php uses 'params' (BEAR.Resource convention).
final class ResourceRequestContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'resource_request';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-request.json';

    /** @param array<string, mixed> $query */
    public function __construct(
        public string $uri,
        public string $method,
        public array $query = [],
        public string $timestamp = '',
    ) {
    }
}
