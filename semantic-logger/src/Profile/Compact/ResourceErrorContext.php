<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;
use Throwable;

use function crc32;
use function sprintf;

/**
 * Compact context for resource error.
 */
final class ResourceErrorContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType upstream parent declares const as literal '' */
    public const TYPE = 'resource.error';
    public const SCHEMA_URL = '';

    public readonly string $id;

    public function __construct(
        public readonly Throwable $exception,
        string|null $id = null,
    ) {
        $this->id = $id ?? sprintf('%08x', crc32($exception->getMessage() . $exception->getFile() . $exception->getLine()));
    }
}
