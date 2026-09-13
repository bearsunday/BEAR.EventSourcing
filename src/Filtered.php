<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Filtering policy: which params keys SemanticLogInvoker removes before recording.
 *
 * Unbound resolves to SensitiveParamsFilter — secure by default, not opt-in — so an application
 * has to supply its own {@see \BEAR\EventSourcing\Resource\ParamsFilterInterface} to widen or
 * narrow what is removed, never to turn removal on in the first place.
 */
#[Attribute(Attribute::TARGET_PARAMETER), Qualifier]
final class Filtered
{
}
