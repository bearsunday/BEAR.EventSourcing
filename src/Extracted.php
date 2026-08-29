<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Extraction policy: which recorded methods become events.
 *
 * A separate key from {@see Recorded} on purpose: development observation may
 * record GET while extraction still treats only writes as state changes.
 */
#[Attribute(Attribute::TARGET_PARAMETER), Qualifier]
final class Extracted
{
}
