<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\XdebugTrace;
use Koriym\SemanticLogger\Profiler\XHProfResult;

/**
 * Verbose context for resource open with profiling initialization.
 *
 * Starts PHP / XHProf / Xdebug profilers; matching stops happen in
 * ContextFactory::collectProfile() when the operation completes.
 */
final class ResourceOpenContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType upstream parent declares const as literal '' */
    public const TYPE = 'resource.open';
    public const SCHEMA_URL = '';

    public readonly PhpProfile $phpProfile;
    public readonly XHProfResult $xhprofResult;
    public readonly XdebugTrace $xdebugTrace;

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        /** @var array<string, mixed> */
        public readonly array $params = [],
        public readonly string|null $callSignature = null,
    ) {
        $this->phpProfile = PhpProfile::start();
        $this->xhprofResult = XHProfResult::start();
        $this->xdebugTrace = XdebugTrace::start();
    }
}
