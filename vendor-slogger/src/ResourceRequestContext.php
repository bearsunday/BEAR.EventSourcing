<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use Koriym\SemanticLogger\AbstractContext;

final class ResourceRequestContext extends AbstractContext
{
    public const TYPE = 'resource_request';
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-request.json';

    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $query = [],
    ) {
    }
}
