<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Captures the intent of a resource request at initiation.
 */
interface OpenContextInterface
{
    /**
     * Get the HTTP method (GET, POST, PUT, PATCH, DELETE).
     */
    public function getMethod(): string;

    /**
     * Get the resource URI.
     */
    public function getUri(): string;

    /**
     * Get the request parameters.
     *
     * @return array<string, mixed>
     */
    public function getParams(): array;

    /**
     * Get the underlying context for SemanticLogger.
     */
    public function getContext(): AbstractContext;
}
