<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\Profile;
use Override;
use Throwable;

use function crc32;
use function sprintf;

/**
 * Verbose context for resource error with profiling data.
 */
final class ResourceErrorContext extends AbstractContext implements JsonSerializable
{
    public const TYPE = 'resource.error';
    public const SCHEMA_URL = '';

    public readonly string $id;

    public function __construct(
        public readonly Throwable $exception,
        public readonly Profile|null $profile = null,
        string|null $id = null,
    ) {
        $this->id = $id ?? sprintf('%08x', crc32($exception->getMessage() . $exception->getFile() . $exception->getLine()));
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'exception' => [
                'class' => $this->exception::class,
                'message' => $this->exception->getMessage(),
                'code' => $this->exception->getCode(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
            ],
        ];

        if ($this->profile !== null) {
            $data['profile'] = $this->profile->jsonSerialize();
        }

        return $data;
    }
}
