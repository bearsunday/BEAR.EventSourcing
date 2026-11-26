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
        public readonly string|null $uri,
        public readonly int|null $code,
        public readonly array|null $exception,
        public readonly float $durationMs,
    ) {
    }

    /**
     * The resource object the linker returned: from bear/resource 1.37, the last one followed or the one that
     * answered 400 or more and stopped the links
     */
    public static function ended(string $uri, int $code, float $durationMs): self
    {
        return new self($uri, $code, null, $durationMs);
    }

    public static function failure(Throwable $exception, float $durationMs): self
    {
        return new self(null, null, ['class' => $exception::class, 'message' => $exception->getMessage()], $durationMs);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $context = [];
        if ($this->uri !== null) {
            $context['uri'] = $this->uri;
        }

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
