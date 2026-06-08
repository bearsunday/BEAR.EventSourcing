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
     * @param string               $timestamp ISO 8601 (Y-m-d\TH:i:s.uP) — when the call happened.
     *                                        Empty string means "unknown / capture-time" and
     *                                        downstream consumers should fall back to "now".
     */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $query = [],
        public readonly string $timestamp = '',
    ) {
    }
}
