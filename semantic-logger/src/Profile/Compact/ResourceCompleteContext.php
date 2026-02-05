<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Compact context for resource completion.
 */
final class ResourceCompleteContext extends AbstractContext
{
    public const TYPE = 'resource.complete';
    public const SCHEMA_URL = '';

    public function __construct(
        public readonly string $uri,
        public readonly int $code,
        /** @var array<string, string> */
        public readonly array $headers,
        public readonly mixed $body,
        public readonly string|null $view = null,
    ) {
    }
}
