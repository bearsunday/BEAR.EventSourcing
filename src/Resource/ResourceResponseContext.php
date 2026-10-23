<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;
use Throwable;

final class ResourceResponseContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'resource_response';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/resource-response.json';

    /**
     * @param non-empty-string|null                            $bodyRef
     * @param array{class: class-string, message: string}|null $exception
     */
    private function __construct(
        public readonly int|null $code,
        public readonly string|null $bodyRef = null,
        public readonly array|null $exception = null,
        public readonly float|null $durationMs = null,
    ) {
    }

    /**
     * A resource invocation that completed with a known HTTP status.
     *
     * @param non-empty-string|null $bodyRef
     */
    public static function success(int $code, string|null $bodyRef = null, float|null $durationMs = null): self
    {
        return new self($code, $bodyRef, null, $durationMs);
    }

    /**
     * A resource invocation that ended in an exception. $code is the exception's own HTTP
     * status when it declared one (400-599); the real status — decided later by the
     * application's error handler, which runs after the observation bridge — is never
     * guessed here. Pass it when it is already known (e.g. a completed response whose
     * body-store write then failed); leave it null for an escaped exception with no
     * declared status of its own.
     */
    public static function failure(Throwable $exception, int|null $code = null, float|null $durationMs = null): self
    {
        return new self($code, null, [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
        ], $durationMs);
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
