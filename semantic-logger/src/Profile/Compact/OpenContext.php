<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\OpenContextInterface;
use Koriym\SemanticLogger\AbstractContext;

/**
 * Compact implementation of OpenContextInterface.
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
    ) {
        $this->context = new ResourceOpenContext($method, $uri, $params);
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
}
