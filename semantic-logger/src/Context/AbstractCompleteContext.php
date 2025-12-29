<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Abstract base for complete context capturing response result.
 */
abstract class AbstractCompleteContext
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $uri,
        public readonly int $code,
        public readonly array $headers,
        public readonly mixed $body,
        public readonly ?string $view,
        public readonly AbstractContext $context,
    ) {
    }
}
