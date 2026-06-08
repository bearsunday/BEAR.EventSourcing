<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Fake;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;

final class FakeCompleteContext extends AbstractCompleteContext
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
    ) {
        parent::__construct(
            $uri,
            $code,
            $headers,
            $body,
            $view,
            new FakeContext(),
        );
    }
}
