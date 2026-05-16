<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message);
    }
}
