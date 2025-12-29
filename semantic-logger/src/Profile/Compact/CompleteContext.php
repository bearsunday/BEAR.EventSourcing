<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\CompleteContextInterface;
use Koriym\SemanticLogger\AbstractContext;

/**
 * Compact implementation of CompleteContextInterface.
 */
final class CompleteContext implements CompleteContextInterface
{
    private readonly ResourceCompleteContext $context;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $uri,
        private readonly int $code,
        private readonly array $headers,
        private readonly mixed $body,
        private readonly ?string $view = null,
    ) {
        $this->context = new ResourceCompleteContext($uri, $code, $headers, $body, $view);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function getContext(): AbstractContext
    {
        return $this->context;
    }
}
