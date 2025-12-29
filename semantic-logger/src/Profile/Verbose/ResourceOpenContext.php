<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Verbose context for resource open with profiling initialization.
 *
 * @psalm-immutable
 */
final class ResourceOpenContext extends AbstractContext
{
    public const TYPE = 'resource.open';
    public const SCHEMA_URL = '';

    public readonly float $startTime;

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        /** @var array<string, mixed> */
        public readonly array $params = [],
        public readonly ?string $callSignature = null,
    ) {
        $this->startTime = microtime(true);

        // Start XHProf if available
        if (function_exists('xhprof_enable')) {
            xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
        }
    }
}
