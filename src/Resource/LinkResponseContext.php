<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;
use Throwable;

final class LinkResponseContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'link_response';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/link-response.json';

    /** @param array{class: class-string, message: string}|null $exception */
    private function __construct(
        public readonly int|null $code,
        public readonly array|null $exception,
        public readonly float $durationMs,
    ) {
    }

    /** @param int $code the code of the resource object the linker returned */
    public static function ended(int $code, float $durationMs): self
    {
        return new self($code, null, $durationMs);
    }

    public static function failure(Throwable $exception, float $durationMs): self
    {
        return new self(null, ['class' => $exception::class, 'message' => $exception->getMessage()], $durationMs);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $context = [];
        if ($this->code !== null) {
            $context['code'] = $this->code;
        }

        if ($this->exception !== null) {
            $context['exception'] = $this->exception;
        }

        $context['durationMs'] = $this->durationMs;

        return $context;
    }
}
