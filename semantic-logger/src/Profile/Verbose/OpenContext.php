<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\SemanticLogger\Context\OpenContextInterface;
use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;

/**
 * Verbose implementation of OpenContextInterface with profiling.
 */
final class OpenContext implements OpenContextInterface
{
    private readonly ResourceOpenContext $context;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $params = [],
        ?string $callSignature = null,
    ) {
        $this->context = new ResourceOpenContext($method, $uri, $params, $callSignature);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getContext(): AbstractContext
    {
        return $this->context;
    }

    /**
     * Get the PhpProfile for profiling.
     */
    public function getPhpProfile(): PhpProfile
    {
        return $this->context->phpProfile;
    }
}
