<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Annotation;

use Attribute;

/**
 * Annotation to require authentication
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequireAuth
{
    /** @param string $type User type required ('customer', 'member', 'any') */
    public function __construct(
        public readonly string $type = 'any',
    ) {
    }
}
