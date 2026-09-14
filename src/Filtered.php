<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Filtering policy: which params values the package's recorders replace with a placeholder
 * before recording (the key stays), and whether the request is still replayable without them.
 *
 * Unbound resolves to SensitiveParamsFilter — secure by default, not opt-in — so an application
 * has to supply its own {@see \BEAR\EventSourcing\Resource\ParamsFilterInterface} to widen or
 * narrow what is replaced, never to turn filtering on in the first place.
 */
#[Attribute(Attribute::TARGET_PARAMETER), Qualifier]
final class Filtered
{
}
