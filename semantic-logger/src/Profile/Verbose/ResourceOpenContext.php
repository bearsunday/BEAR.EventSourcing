<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;

use function function_exists;

/**
 * Verbose context for resource open with profiling initialization.
 */
final class ResourceOpenContext extends AbstractContext
{
    public const TYPE = 'resource.open';
    public const SCHEMA_URL = '';

    public readonly PhpProfile $phpProfile;

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        /** @var array<string, mixed> */
        public readonly array $params = [],
        public readonly string|null $callSignature = null,
    ) {
        $this->phpProfile = new PhpProfile();
        $this->phpProfile->start();

        // Start XHProf if available
        // @codeCoverageIgnoreStart
        if (function_exists('xhprof_enable')) {
            /** @psalm-suppress UndefinedConstant, MixedArgument */
            xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
        }
        // @codeCoverageIgnoreEnd
    }
}
