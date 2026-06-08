<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

use function sprintf;

class ResourceNotFoundException extends RuntimeException
{
    public function __construct(string $resource, int|string $id)
    {
        parent::__construct(sprintf('%s with ID %s not found', $resource, $id));
    }
}
