<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Captures the result of a successful resource request.
 */
interface CompleteContextInterface
{
    /**
     * Get the resource URI.
     */
    public function getUri(): string;

    /**
     * Get the HTTP status code.
     */
    public function getCode(): int;

    /**
     * Get the response headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * Get the response body.
     */
    public function getBody(): mixed;

    /**
     * Get the rendered view (if any).
     */
    public function getView(): ?string;

    /**
     * Get the underlying context for SemanticLogger.
     */
    public function getContext(): AbstractContext;
}
