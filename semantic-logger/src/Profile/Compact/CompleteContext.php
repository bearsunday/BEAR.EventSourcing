<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;

/**
 * Compact implementation of complete context.
 */
final class CompleteContext extends AbstractCompleteContext
{
    /** @param array<string, string> $headers */
    public function __construct(
        string $uri,
        int $code,
        array $headers,
        mixed $body,
        string|null $view = null,
    ) {
        parent::__construct(
            $uri,
            $code,
            $headers,
            $body,
            $view,
            new ResourceCompleteContext($uri, $code, $headers, $body, $view),
        );
    }
}
