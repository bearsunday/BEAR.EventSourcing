<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

class AuthenticationException extends RuntimeException
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message);
    }
}
