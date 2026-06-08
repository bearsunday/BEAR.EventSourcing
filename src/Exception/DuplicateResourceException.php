<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

use function sprintf;

class DuplicateResourceException extends RuntimeException
{
    public function __construct(string $resource, string $field, string $value)
    {
        parent::__construct(sprintf('%s with %s "%s" already exists', $resource, $field, $value));
    }
}
