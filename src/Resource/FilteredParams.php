<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

/**
 * The result of filtering recorded params: what to record, and whether the removal changes
 * what re-executing the operation with those params would mean.
 *
 * Not every removed key blocks replay. A CSRF token is transport, not domain input: it is
 * single-use and session-bound, so a replay engine mints its own regardless of whether the
 * original was recorded — removing it changes nothing about what the recorded params are for.
 * A password is domain input the handler actually reads; removing it leaves the recorded
 * params genuinely insufficient to reproduce the operation. `ParamsFilterInterface`
 * implementations decide this distinction per key, not `SemanticLogInvoker`.
 */
final readonly class FilteredParams
{
    /** @param array<string, mixed> $params */
    public function __construct(
        public array $params,
        public bool $replayable = true,
    ) {
    }
}
