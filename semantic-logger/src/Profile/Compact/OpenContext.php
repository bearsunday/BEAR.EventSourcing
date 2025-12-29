<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\AbstractOpenContext;

/**
 * Compact implementation of open context.
 */
final class OpenContext extends AbstractOpenContext
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        string $method,
        string $uri,
        array $params = [],
    ) {
        parent::__construct(
            $method,
            $uri,
            $params,
            new ResourceOpenContext($method, $uri, $params),
        );
    }
}
