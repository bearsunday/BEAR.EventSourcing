<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Compact context for resource open.
 *
 * @psalm-immutable
 */
final class ResourceOpenContext extends AbstractContext
{
    public const TYPE = 'resource.open';
    public const SCHEMA_URL = '';

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        /** @var array<string, mixed> */
        public readonly array $params = [],
    ) {
    }
}
