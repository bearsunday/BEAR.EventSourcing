<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Attribute;
use Ray\Di\Di\Qualifier;

/** Recording policy: which methods the observation bridge writes to the log. */
#[Attribute(Attribute::TARGET_PARAMETER), Qualifier]
final class Recorded
{
}
