<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use RuntimeException;

/** Thrown when a ResourceResponseContext is constructed without a code or an exception. */
final class ResourceResponseContextException extends RuntimeException
{
}
