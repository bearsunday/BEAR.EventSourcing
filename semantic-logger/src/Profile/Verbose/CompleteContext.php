<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use Koriym\SemanticLogger\Profiler\Profile;

/**
 * Verbose implementation of complete context with profiling.
 */
final class CompleteContext extends AbstractCompleteContext
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $uri,
        int $code,
        array $headers,
        mixed $body,
        ?string $view = null,
        public readonly ?Profile $profile = null,
    ) {
        parent::__construct(
            $uri,
            $code,
            $headers,
            $body,
            $view,
            new ResourceCompleteContext($uri, $code, $headers, $body, $view, $profile),
        );
    }
}
