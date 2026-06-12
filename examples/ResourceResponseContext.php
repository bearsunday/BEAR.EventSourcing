<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;

final class ResourceResponseContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'resource_response';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-response.json';

    /** @param non-empty-string|null $viewRef */
    public function __construct(
        public readonly int $code,
        public readonly string|null $viewRef = null,
    ) {
    }

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        $context = ['code' => $this->code];
        if ($this->viewRef !== null) {
            $context['view_ref'] = $this->viewRef;
        }

        return $context;
    }
}
