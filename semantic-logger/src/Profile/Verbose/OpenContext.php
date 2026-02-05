<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\SemanticLogger\Context\AbstractOpenContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;

/**
 * Verbose implementation of open context with profiling.
 */
final class OpenContext extends AbstractOpenContext
{
    public readonly PhpProfile $phpProfile;

    /** @param array<string, mixed> $params */
    public function __construct(
        string $method,
        string $uri,
        array $params = [],
        public readonly string|null $callSignature = null,
    ) {
        $resourceContext = new ResourceOpenContext($method, $uri, $params, $callSignature);
        $this->phpProfile = $resourceContext->phpProfile;

        parent::__construct(
            $method,
            $uri,
            $params,
            $resourceContext,
        );
    }
}
