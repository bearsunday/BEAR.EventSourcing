<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use Koriym\SemanticLogger\AbstractContext;

final class ResourceResponseContext extends AbstractContext
{
    public const TYPE = 'resource_response';
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-response.json';

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public readonly int $code,
        public readonly mixed $body,
        public readonly array $headers = [],
    ) {
    }
}
