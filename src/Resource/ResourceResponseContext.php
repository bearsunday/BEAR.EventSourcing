<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;

final class ResourceResponseContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'resource_response';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-response.json';

    /**
     * @param non-empty-string|null                          $bodyRef
     * @param array{class: class-string, message: string}|null $exception
     */
    public function __construct(
        public readonly int $code,
        public readonly string|null $bodyRef = null,
        public readonly array|null $exception = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $context = ['code' => $this->code];
        if ($this->bodyRef !== null) {
            $context['body_ref'] = $this->bodyRef;
        }

        if ($this->exception !== null) {
            $context['exception'] = $this->exception;
        }

        return $context;
    }
}
