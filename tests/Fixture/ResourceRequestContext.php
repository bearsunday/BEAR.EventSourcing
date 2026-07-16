<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use Koriym\SemanticLogger\AbstractContext;

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
        public string $timestamp = '2026-06-10T12:34:56.123456+00:00',
    ) {
    }
}
