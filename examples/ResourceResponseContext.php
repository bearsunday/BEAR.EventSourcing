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

    /** @param array<string, mixed>|null $body */
    public function __construct(
        public readonly int $code,
        public readonly array|null $body = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $context = ['code' => $this->code];
        if ($this->body !== null) {
            $context['body'] = $this->body;
        }

        return $context;
    }
}
