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
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/resource-response.json';

    /**
     * @param non-empty-string|null                          $bodyRef
     * @param array{class: class-string, message: string}|null $exception
     *
     * @throws ResourceResponseContextException if both $code and $exception are null.
     */
    public function __construct(
        public readonly int|null $code,
        public readonly string|null $bodyRef = null,
        public readonly array|null $exception = null,
        public readonly float|null $durationMs = null,
    ) {
        if ($this->code === null && $this->exception === null) {
            throw new ResourceResponseContextException('ResourceResponseContext requires a code or an exception.');
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $context = [];
        if ($this->code !== null) {
            $context['code'] = $this->code;
        }

        if ($this->bodyRef !== null) {
            $context['body_ref'] = $this->bodyRef;
        }

        if ($this->exception !== null) {
            $context['exception'] = $this->exception;
        }

        if ($this->durationMs !== null) {
            $context['durationMs'] = $this->durationMs;
        }

        return $context;
    }
}
